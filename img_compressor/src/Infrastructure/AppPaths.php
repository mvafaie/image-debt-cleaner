<?php

declare(strict_types=1);

namespace ImgCompressor\Infrastructure;

use ImgCompressor\Config\AppConfig;

final class AppPaths
{
    private string $documentRoot;
    private string $compressorRelativePath;
    private string $appBasePath;

    private function __construct(
        private readonly string $compressorRoot,
        private readonly ?string $documentRootOverride,
        private readonly ?string $basePathOverride,
        private readonly string $assetsUrlPrefix,
    ) {
    }

    public static function create(string $compressorRoot, AppConfig $config): self
    {
        $instance = new self(
            $compressorRoot,
            $config->documentRootOverride(),
            $config->basePathOverride(),
            $config->assetsUrlPrefix(),
        );
        $instance->documentRoot = $instance->detectDocumentRoot($config);
        $instance->compressorRelativePath = $instance->detectCompressorRelativePath();
        $instance->appBasePath = $instance->detectAppBasePath();

        return $instance;
    }

    public function compressorRoot(): string
    {
        return $this->compressorRoot;
    }

    /** Absolute filesystem path of the web document root. */
    public function documentRoot(): string
    {
        return $this->documentRoot;
    }

    /** @deprecated Use documentRoot() — kept for internal callers */
    public function publicRoot(): string
    {
        return $this->documentRoot();
    }

    /** Path of this tool relative to document root, e.g. "admin/tools/img_compressor". */
    public function compressorRelativePath(): string
    {
        return $this->compressorRelativePath;
    }

    public function backupRoot(AppConfig $config): string
    {
        return $this->compressorRoot . '/' . trim($config->backupDir(), '/');
    }

    /** URL prefix for this tool, e.g. "/admin/tools/img_compressor/" */
    public function appBasePath(): string
    {
        return $this->appBasePath;
    }

    /** Cookie path without trailing slash — matches /dir and /dir/... */
    public function sessionCookiePath(): string
    {
        $base = rtrim($this->appBasePath(), '/');

        return $base === '' ? '/' : $base;
    }

    public function redirectToCanonicalUrlIfNeeded(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        if (isset($_GET['action'])) {
            return;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim($this->appBasePath(), '/');
        if ($base === '' || $base === '/' || $uri !== $base) {
            return;
        }

        $query = $_SERVER['QUERY_STRING'] ?? '';
        $target = $base . '/' . ($query !== '' ? '?' . $query : '');
        header('Location: ' . $target, true, 301);
        exit;
    }

    public function assetUrl(string $path): string
    {
        return $this->appBasePath() . ltrim(str_replace('\\', '/', $path), '/');
    }

    public function apiUrl(): string
    {
        return $this->appBasePath() . 'index.php';
    }

    /** Public URL for a scanned image relative to document root. */
    public function fileUrl(string $relativePath): string
    {
        $path = ltrim(str_replace('\\', '/', $relativePath), '/');
        $prefix = $this->assetsUrlPrefix;

        if ($prefix === '' || $prefix === '/') {
            return '/' . $path;
        }

        return rtrim($prefix, '/') . '/' . $path;
    }

    /** @return array{base: string, api: string, files: string} */
    public function toClientPayload(): array
    {
        return [
            'base' => $this->appBasePath(),
            'api' => $this->apiUrl(),
            'files' => $this->assetsUrlPrefix,
        ];
    }

    public function relativeToDocumentRoot(string $absolutePath): ?string
    {
        $real = realpath($absolutePath);
        if ($real === false) {
            return null;
        }

        $root = $this->documentRoot();
        if ($real !== $root && !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');

        return $relative === '' ? null : $relative;
    }

    public function isInsideCompressor(string $relativePath): bool
    {
        $prefix = $this->compressorRelativePath();
        if ($prefix === '') {
            return false;
        }

        return $relativePath === $prefix || str_starts_with($relativePath, $prefix . '/');
    }

    private function detectDocumentRoot(AppConfig $config): string
    {
        if ($this->documentRootOverride !== null && $this->documentRootOverride !== '') {
            $real = realpath($this->documentRootOverride);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }

        $compressorReal = realpath($this->compressorRoot) ?: $this->compressorRoot;

        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $docRoot = realpath((string) $_SERVER['DOCUMENT_ROOT']);
            if ($docRoot !== false
                && ($compressorReal === $docRoot || str_starts_with($compressorReal, $docRoot . DIRECTORY_SEPARATOR))) {
                return $docRoot;
            }
        }

        foreach ($config->scanPaths() as $scanPath) {
            if (str_starts_with($scanPath, '/')) {
                continue;
            }

            $dir = dirname($compressorReal);
            $stop = dirname($dir);
            while ($dir !== $stop) {
                $candidate = $dir . '/' . ltrim($scanPath, '/');
                if (is_dir($candidate)) {
                    return $dir;
                }
                $dir = dirname($dir);
            }
        }

        return realpath(dirname($compressorReal)) ?: dirname($compressorReal);
    }

    private function detectCompressorRelativePath(): string
    {
        $compressorReal = realpath($this->compressorRoot) ?: $this->compressorRoot;
        $root = $this->documentRoot;

        if ($compressorReal === $root) {
            return '';
        }

        if (!str_starts_with($compressorReal, $root . DIRECTORY_SEPARATOR)) {
            return basename($compressorReal);
        }

        return ltrim(str_replace('\\', '/', substr($compressorReal, strlen($root))), '/');
    }

    private function detectAppBasePath(): string
    {
        if ($this->basePathOverride !== null && $this->basePathOverride !== '') {
            $base = str_replace('\\', '/', $this->basePathOverride);

            return str_ends_with($base, '/') ? $base : $base . '/';
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== '' && !str_ends_with($script, '.php')) {
            return rtrim($script, '/') . '/';
        }

        if ($script !== '') {
            $dir = dirname($script);
            if ($dir === '/' || $dir === '.') {
                $relative = $this->compressorRelativePath;

                return $relative !== '' ? '/' . $relative . '/' : '/';
            }

            return rtrim($dir, '/') . '/';
        }

        $relative = $this->compressorRelativePath;

        return $relative === '' ? '/' : '/' . $relative . '/';
    }
}

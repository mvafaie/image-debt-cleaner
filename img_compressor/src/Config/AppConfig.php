<?php

declare(strict_types=1);

namespace ImgCompressor\Config;

use ImgCompressor\Infrastructure\AppPaths;

final class AppConfig
{
    /** @param array<string, mixed> $data */
    private function __construct(private readonly array $data)
    {
    }

    public static function load(string $baseDir): self
    {
        $data = require $baseDir . '/config.php';
        $local = $baseDir . '/config.local.php';
        if (is_file($local)) {
            $data = array_replace_recursive($data, require $local);
        }

        return new self($data);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function allowedUserAgent(): string
    {
        return (string) $this->get('allowed_user_agent', '');
    }

    public function sessionLifetime(): int
    {
        return (int) $this->get('session_lifetime', 28800);
    }

    public function loginMaxAttempts(): int
    {
        return (int) $this->get('login_max_attempts', 5);
    }

    public function loginLockoutSeconds(): int
    {
        return (int) $this->get('login_lockout_seconds', 900);
    }

    public function maxUploadBytes(): int
    {
        return (int) $this->get('max_upload_bytes', 52428800);
    }

    public function minFileSize(): int
    {
        return (int) $this->get('min_file_size', 1048576);
    }

    public function perPage(): int
    {
        return (int) $this->get('per_page', 20);
    }

    public function perPageMin(): int
    {
        return (int) $this->get('per_page_min', 5);
    }

    public function perPageMax(): int
    {
        return (int) $this->get('per_page_max', 100);
    }

    /** @return list<string> */
    public function allowedExtensions(): array
    {
        return $this->get('allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp']);
    }

    public function isBackupEnabled(): bool
    {
        return !empty($this->data['backup_enabled']);
    }

    public function backupDir(): string
    {
        return (string) $this->get('backup_dir', 'assets/backups');
    }

    /** @return list<int> */
    public function qualityLevels(): array
    {
        return $this->get('quality_levels', [90, 80, 70, 60, 50, 40, 30, 20]);
    }

    public function fallbackLocale(): string
    {
        return (string) ($this->data['i18n']['fallback_locale'] ?? 'en');
    }

    /** Absolute path override for web document root (null = auto-detect). */
    public function documentRootOverride(): ?string
    {
        $value = $this->get('document_root');
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /** URL path override for this tool, e.g. "/admin/img_compressor/" (null = auto-detect). */
    public function basePathOverride(): ?string
    {
        $value = $this->get('base_path');
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /** URL prefix for scanned image files (default "/" = site root). */
    public function assetsUrlPrefix(): string
    {
        $prefix = $this->get('assets_url_prefix', '/');

        return $prefix === null || $prefix === '' ? '/' : (string) $prefix;
    }

    /** @return list<string> */
    public function scanPaths(): array
    {
        $paths = $this->data['scan_paths'] ?? [];

        if ($paths === [] && !empty($this->data['images_dir'])) {
            $paths[] = $this->data['images_dir'];
        }
        if (!empty($this->data['hardcoded_paths'])) {
            $paths = array_merge($paths, $this->data['hardcoded_paths']);
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /** @return list<string> */
    public function scanRoots(AppPaths $paths): array
    {
        $documentRoot = $paths->documentRoot();
        $roots = [];

        foreach ($this->scanPaths() as $path) {
            $full = str_starts_with($path, '/') ? $path : $documentRoot . '/' . ltrim($path, '/');
            $real = realpath($full);
            if ($real !== false && is_dir($real)) {
                $roots[] = $real;
            }
        }

        return $roots;
    }

    public function isAllowedExtension(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($ext, $this->allowedExtensions(), true);
    }
}

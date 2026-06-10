<?php

declare(strict_types=1);

namespace ImgCompressor\Infrastructure;

use ImgCompressor\Config\AppConfig;
use ImgCompressor\Http\JsonResponse;

final class PathGuard
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
    ) {
    }

    public function normalizeRelativePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        $parts = explode('/', $path);
        $safe = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                return null;
            }
            $safe[] = $part;
        }

        return $safe === [] ? null : implode('/', $safe);
    }

    public function resolvePublicPath(string $relativePath, bool $mustExist = true): ?string
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if ($normalized === null) {
            return null;
        }

        $publicRoot = realpath($this->paths->publicRoot());
        if ($publicRoot === false) {
            return null;
        }

        $candidate = $publicRoot . '/' . $normalized;
        if ($mustExist) {
            $resolved = realpath($candidate);
            if ($resolved === false || !is_file($resolved)) {
                return null;
            }

            return str_starts_with($resolved, $publicRoot . DIRECTORY_SEPARATOR) ? $resolved : null;
        }

        $parent = realpath(dirname($candidate));
        if ($parent === false || !str_starts_with($parent, $publicRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $candidate;
    }

    public function isPathInScanRoots(string $absolutePath): bool
    {
        $real = realpath($absolutePath);
        if ($real === false) {
            return false;
        }

        foreach ($this->config->scanRoots($this->paths) as $root) {
            if ($real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    public function isPathAllowed(string $relativePath): bool
    {
        $resolved = $this->resolvePublicPath($relativePath, true);

        return $resolved !== null && $this->isPathInScanRoots($resolved);
    }

    public function isWritePathAllowed(string $relativePath): bool
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if ($normalized === null || !$this->config->isAllowedExtension(basename($normalized))) {
            return false;
        }

        $resolved = $this->resolvePublicPath(
            $normalized,
            is_file($this->paths->publicRoot() . '/' . $normalized),
        );
        if ($resolved === null) {
            return false;
        }

        return $this->isPathInScanRoots($resolved);
    }

    public function assertAllowedUploadSize(string $base64Payload): void
    {
        $estimated = (int) (strlen($base64Payload) * 3 / 4);
        if ($estimated > $this->config->maxUploadBytes()) {
            JsonResponse::send(['ok' => false, 'error' => 'File too large'], 413);
        }
    }

    public function assertBackupPathSafe(string $backupPath): bool
    {
        $backupRoot = realpath($this->paths->backupRoot($this->config));
        $realBackup = realpath(dirname($backupPath));
        if ($backupRoot === false || $realBackup === false) {
            return false;
        }

        return $realBackup === $backupRoot || str_starts_with($realBackup, $backupRoot . DIRECTORY_SEPARATOR);
    }
}

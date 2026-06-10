<?php

declare(strict_types=1);

namespace ImgCompressor\Infrastructure;

use ImgCompressor\Config\AppConfig;
use ImgCompressor\Http\JsonResponse;

final class BackupStore
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
        private readonly PathGuard $pathGuard,
        private readonly I18n $i18n,
    ) {
    }

    public function ensureDir(): string
    {
        $dir = $this->paths->backupRoot($this->config);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.backup_dir')], 500);
        }

        return $dir;
    }

    public function buildPath(string $originalRelativePath): string
    {
        $relative = $this->pathGuard->normalizeRelativePath($originalRelativePath);
        if ($relative === null) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.path_forbidden')], 403);
        }

        $timestamp = date('YmdHis');
        $backupBase = $this->ensureDir();
        $dir = dirname($relative);
        $name = basename($relative);

        if ($dir !== '.') {
            $targetDir = $backupBase . '/' . $dir;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.backup_dir')], 500);
            }

            return $targetDir . '/' . $name . '.' . $timestamp . '.bak';
        }

        return $backupBase . '/' . $name . '.' . $timestamp . '.bak';
    }

    public function backupOriginal(string $fullPath, string $relativePath): ?string
    {
        if (!$this->config->isBackupEnabled()) {
            return null;
        }

        $backupPath = $this->buildPath($relativePath);
        if (!$this->pathGuard->assertBackupPathSafe($backupPath)) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.path_forbidden')], 403);
        }
        if (!copy($fullPath, $backupPath)) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.backup_create')], 500);
        }

        return basename($backupPath);
    }
}

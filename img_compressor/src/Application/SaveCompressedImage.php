<?php

declare(strict_types=1);

namespace ImgCompressor\Application;

use ImgCompressor\Config\AppConfig;
use ImgCompressor\Domain\ByteFormatter;
use ImgCompressor\Http\JsonResponse;
use ImgCompressor\Http\Request;
use ImgCompressor\Infrastructure\AppPaths;
use ImgCompressor\Infrastructure\BackupStore;
use ImgCompressor\Infrastructure\I18n;
use ImgCompressor\Infrastructure\PathGuard;
use ImgCompressor\Infrastructure\SessionAuth;

final class SaveCompressedImage
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
        private readonly PathGuard $pathGuard,
        private readonly BackupStore $backupStore,
        private readonly SessionAuth $sessionAuth,
        private readonly I18n $i18n,
    ) {
    }

    public function handle(Request $request): void
    {
        $this->sessionAuth->verifyCsrf($request);

        $path = (string) $request->post('path', '');
        $imageData = (string) $request->post('image', '');

        if ($path === '' || $imageData === '') {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.incomplete_data')], 400);
        }

        if (!$this->pathGuard->isWritePathAllowed($path)) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.path_forbidden')], 403);
        }

        $fullPath = $this->pathGuard->resolvePublicPath($path, true);
        if ($fullPath === null) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.file_not_found')], 404);
        }

        if (!preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $imageData)) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.invalid_image_format')], 400);
        }

        $base64 = substr($imageData, strpos($imageData, ',') + 1);
        $this->pathGuard->assertAllowedUploadSize($base64);

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.base64_decode')], 400);
        }

        $backupName = $this->backupStore->backupOriginal($fullPath, $path);

        $normalizedPath = $this->pathGuard->normalizeRelativePath($path);
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $outputPath = $fullPath;
        $newRelative = $normalizedPath;

        if (!in_array($ext, ['jpg', 'jpeg'], true)) {
            $outputPath = preg_replace('/\.[^.]+$/', '.jpg', $fullPath);
            $newRelative = preg_replace('/\.[^.]+$/', '.jpg', $normalizedPath ?? $path);
            $parent = realpath(dirname($outputPath));
            $publicRoot = realpath($this->paths->publicRoot());
            if ($parent === false || $publicRoot === false || !str_starts_with($parent, $publicRoot . DIRECTORY_SEPARATOR)) {
                JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.path_forbidden')], 403);
            }
        }

        if (file_put_contents($outputPath, $binary) === false) {
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.save_failed')], 500);
        }

        if ($outputPath !== $fullPath && file_exists($fullPath)) {
            unlink($fullPath);
        }

        JsonResponse::send([
            'ok' => true,
            'path' => $newRelative,
            'new_size' => filesize($outputPath),
            'new_size_human' => ByteFormatter::format((int) filesize($outputPath)),
            'backup' => $backupName,
            'backup_enabled' => $this->config->isBackupEnabled(),
        ]);
    }
}

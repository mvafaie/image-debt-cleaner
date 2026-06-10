<?php

declare(strict_types=1);

use ImgCompressor\Application\ListOversizedImages;
use ImgCompressor\Application\SaveCompressedImage;
use ImgCompressor\Application\ShowApp;
use ImgCompressor\Config\AppConfig;
use ImgCompressor\Http\Router;
use ImgCompressor\Infrastructure\AppPaths;
use ImgCompressor\Infrastructure\BackupStore;
use ImgCompressor\Infrastructure\FileScanner;
use ImgCompressor\Infrastructure\I18n;
use ImgCompressor\Infrastructure\PathGuard;
use ImgCompressor\Infrastructure\SecurityHeaders;
use ImgCompressor\Infrastructure\SessionAuth;

$baseDir = __DIR__;

spl_autoload_register(static function (string $class) use ($baseDir): void {
    $prefix = 'ImgCompressor\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = AppConfig::load($baseDir);
$paths = AppPaths::create($baseDir, $config);
$paths->redirectToCanonicalUrlIfNeeded();

SessionAuth::startSession($config, $paths);

$i18n = I18n::boot($config, $baseDir . '/lang');
$sessionAuth = new SessionAuth($config, $paths, $i18n);
$pathGuard = new PathGuard($config, $paths);

SecurityHeaders::send();
$sessionAuth->requireAccess();

$fileScanner = new FileScanner($config, $paths);
$backupStore = new BackupStore($config, $paths, $pathGuard, $i18n);

return new Router(
    $config,
    $paths,
    $sessionAuth,
    $i18n,
    new ListOversizedImages($config, $paths, $fileScanner, $i18n),
    new SaveCompressedImage($config, $paths, $pathGuard, $backupStore, $sessionAuth, $i18n),
    new ShowApp($config, $paths, $sessionAuth, $i18n, $baseDir . '/views'),
);

<?php

declare(strict_types=1);

namespace ImgCompressor\Application;

use ImgCompressor\Config\AppConfig;
use ImgCompressor\Infrastructure\AppPaths;
use ImgCompressor\Infrastructure\I18n;
use ImgCompressor\Infrastructure\SessionAuth;

final class ShowApp
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
        private readonly SessionAuth $sessionAuth,
        private readonly I18n $i18n,
        private readonly string $viewsDir,
    ) {
    }

    public function handle(): void
    {
        $authenticated = $this->sessionAuth->isAuthenticated();
        $htmlLocale = $this->i18n->getLocale();
        $htmlDir = $this->i18n->getDirection();

        $config = $this->config;
        $i18n = $this->i18n;
        $paths = $this->paths;
        $csrfToken = $this->sessionAuth->csrfToken();
        $i18nPayload = $this->i18n->toPayload();

        require $this->viewsDir . '/app.php';
    }
}

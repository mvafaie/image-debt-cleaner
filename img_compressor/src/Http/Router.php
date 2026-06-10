<?php

declare(strict_types=1);

namespace ImgCompressor\Http;

use ImgCompressor\Application\ListOversizedImages;
use ImgCompressor\Application\SaveCompressedImage;
use ImgCompressor\Application\ShowApp;
use ImgCompressor\Config\AppConfig;
use ImgCompressor\Infrastructure\AppPaths;
use ImgCompressor\Infrastructure\I18n;
use ImgCompressor\Infrastructure\SessionAuth;

final class Router
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
        private readonly SessionAuth $sessionAuth,
        private readonly I18n $i18n,
        private readonly ListOversizedImages $listOversizedImages,
        private readonly SaveCompressedImage $saveCompressedImage,
        private readonly ShowApp $showApp,
    ) {
    }

    public function dispatch(Request $request): void
    {
        $action = $request->action();

        if ($action === 'login' && $request->isPost()) {
            $this->sessionAuth->login($request);
        }

        if ($action === 'logout') {
            $this->sessionAuth->logout();
        }

        if ($action === 'check') {
            JsonResponse::send([
                'authenticated' => $this->sessionAuth->isAuthenticated(),
                'csrf_token' => $this->sessionAuth->csrfToken(),
                'quality_levels' => $this->config->qualityLevels(),
                'backup_enabled' => $this->config->isBackupEnabled(),
                'paths' => $this->paths->toClientPayload(),
                'i18n' => $this->i18n->toPayload(),
            ]);
        }

        if ($action === 'locale') {
            $lang = (string) ($request->query('lang') ?? $request->post('lang', ''));
            if ($lang !== '' && $this->i18n->setLocale($lang)) {
                JsonResponse::send(['ok' => true, 'i18n' => $this->i18n->toPayload()]);
            }
            JsonResponse::send(['ok' => false, 'error' => $this->i18n->get('error.locale_invalid')], 400);
        }

        if ($action === 'files') {
            if (!$this->sessionAuth->isAuthenticated()) {
                JsonResponse::send(['error' => $this->i18n->get('error.unauthorized')], 401);
            }
            $this->listOversizedImages->handle($request);
        }

        if ($action === 'save') {
            if (!$this->sessionAuth->isAuthenticated()) {
                JsonResponse::send(['error' => $this->i18n->get('error.unauthorized')], 401);
            }
            if (!$request->isPost()) {
                JsonResponse::send(['error' => $this->i18n->get('error.method_not_allowed')], 405);
            }
            $this->saveCompressedImage->handle($request);
        }

        $this->showApp->handle();
    }
}

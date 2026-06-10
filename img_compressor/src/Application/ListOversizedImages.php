<?php

declare(strict_types=1);

namespace ImgCompressor\Application;

use ImgCompressor\Config\AppConfig;
use ImgCompressor\Http\JsonResponse;
use ImgCompressor\Http\Request;
use ImgCompressor\Infrastructure\AppPaths;
use ImgCompressor\Infrastructure\FileScanner;
use ImgCompressor\Infrastructure\I18n;

final class ListOversizedImages
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
        private readonly FileScanner $fileScanner,
        private readonly I18n $i18n,
    ) {
    }

    public function handle(Request $request): void
    {
        $files = $this->fileScanner->scan();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = $this->resolvePerPage($request);
        $total = count($files);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageFiles = array_slice($files, $offset, $perPage);

        JsonResponse::send([
            'files' => array_map(fn($f) => $f->toListArray($this->paths), $pageFiles),
            'paths' => $this->paths->toClientPayload(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'quality_levels' => $this->config->qualityLevels(),
            'backup_enabled' => $this->config->isBackupEnabled(),
            'per_page_min' => $this->config->perPageMin(),
            'per_page_max' => $this->config->perPageMax(),
            'per_page_default' => $this->config->perPage(),
            'i18n' => $this->i18n->toPayload(),
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $requested = (int) $request->query('per_page', $this->config->perPage());

        return max(
            $this->config->perPageMin(),
            min($this->config->perPageMax(), $requested > 0 ? $requested : $this->config->perPage()),
        );
    }
}

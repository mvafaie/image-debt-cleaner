<?php

declare(strict_types=1);

namespace ImgCompressor\Infrastructure;

use FilesystemIterator;
use ImgCompressor\Config\AppConfig;
use ImgCompressor\Domain\ImageFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FileScanner
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly AppPaths $paths,
    ) {
    }

    /** @return list<ImageFile> */
    public function scan(): array
    {
        $rows = [];

        foreach ($this->config->scanRoots($this->paths) as $root) {
            $this->collectFromDir($root, $rows);
        }

        $unique = [];
        foreach ($rows as $row) {
            $unique[$row['path']] = $row;
        }

        $files = array_map(ImageFile::fromScanRow(...), array_values($unique));
        usort($files, fn(ImageFile $a, ImageFile $b) => $b->size <=> $a->size);

        return $files;
    }

    /** @param list<array{path: string, name: string, size: int, modified: int}> $files */
    private function collectFromDir(string $dir, array &$files): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $documentRoot = $this->paths->documentRoot();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !$this->config->isAllowedExtension($file->getFilename())) {
                continue;
            }

            if (str_contains($file->getFilename(), '.bak.')) {
                continue;
            }

            $size = $file->getSize();
            if ($size < $this->config->minFileSize()) {
                continue;
            }

            $relative = $this->paths->relativeToDocumentRoot($file->getPathname());
            if ($relative === null) {
                continue;
            }

            if ($this->paths->isInsideCompressor($relative)) {
                continue;
            }

            $files[] = [
                'path' => $relative,
                'name' => $file->getFilename(),
                'size' => $size,
                'modified' => $file->getMTime(),
            ];
        }
    }
}

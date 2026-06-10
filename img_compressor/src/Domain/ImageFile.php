<?php

declare(strict_types=1);

namespace ImgCompressor\Domain;

use ImgCompressor\Infrastructure\AppPaths;

final class ImageFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly int $modified,
    ) {
    }

    public static function fromScanRow(array $row): self
    {
        return new self(
            $row['path'],
            $row['name'],
            $row['size'],
            $row['modified'],
        );
    }

    public function folder(): string
    {
        $dir = dirname(str_replace('\\', '/', $this->path));

        return $dir === '.' ? '' : $dir;
    }

    /** @return array<string, mixed> */
    public function toListArray(AppPaths $paths): array
    {
        return [
            'path' => $this->path,
            'name' => $this->name,
            'size' => $this->size,
            'modified' => $this->modified,
            'url' => $paths->fileUrl($this->path),
            'folder' => $this->folder(),
            'size_human' => ByteFormatter::format($this->size),
            'modified_human' => date('Y-m-d H:i', $this->modified),
        ];
    }
}

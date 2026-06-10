<?php

declare(strict_types=1);

namespace ImgCompressor\Http;

final class Request
{
    public static function fromGlobals(): self
    {
        return new self();
    }

    public function action(): string
    {
        return (string) ($_GET['action'] ?? '');
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $_SERVER[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }
}

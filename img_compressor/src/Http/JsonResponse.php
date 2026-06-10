<?php

declare(strict_types=1);

namespace ImgCompressor\Http;

final class JsonResponse
{
    /** @param array<string, mixed> $data */
    public static function send(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

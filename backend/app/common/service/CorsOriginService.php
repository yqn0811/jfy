<?php

namespace app\common\service;

class CorsOriginService
{
    const DEFAULT_ALLOWED_ORIGINS = 'https://file.jfyuntu.com,https://file-test.jfyuntu.com,https://pic.jfyuntu.com,https://pic-test.jfyuntu.com,https://api.jfyuntu.com,https://api-test.jfyuntu.com,http://localhost:5178,http://127.0.0.1:5178';

    public static function resolveAllowedOrigin(string $origin): string
    {
        $origin = trim($origin);
        if ($origin === '' || preg_match('/[\r\n]/', $origin)) {
            return '';
        }

        $allowed = self::allowedOrigins();
        if (in_array($origin, $allowed, true)) {
            return $origin;
        }

        return '';
    }

    public static function allowCredentials(string $origin): bool
    {
        return $origin !== '';
    }

    private static function allowedOrigins(): array
    {
        $configured = (string)env('file_transfer.allowed_origins', self::DEFAULT_ALLOWED_ORIGINS);
        $items = array_filter(array_map('trim', explode(',', $configured)), function ($origin) {
            return $origin !== '' && !preg_match('/[\r\n]/', $origin);
        });

        return array_values(array_unique($items));
    }
}

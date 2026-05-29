<?php

declare(strict_types=1);

namespace Inkstone\Support;

final class UrlBuilder
{
    public static function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '' || $baseUrl === '/') {
            return '';
        }

        if (! str_starts_with($baseUrl, '/') && preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $baseUrl) !== 1) {
            $baseUrl = '/'.$baseUrl;
        }

        return rtrim($baseUrl, '/');
    }

    public static function to(string $baseUrl, string $path = ''): string
    {
        $baseUrl = self::normalizeBaseUrl($baseUrl);
        $path = ltrim($path, '/');

        if ($path === '') {
            return $baseUrl !== '' ? $baseUrl : '/';
        }

        return ($baseUrl !== '' ? $baseUrl : '').'/'.$path;
    }
}

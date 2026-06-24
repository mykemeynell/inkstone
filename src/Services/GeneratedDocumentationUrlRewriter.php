<?php

declare(strict_types=1);

namespace Inkstone\Services;

final class GeneratedDocumentationUrlRewriter
{
    public function __construct(private readonly GeneratedDocumentationFileServer $files) {}

    public function rewrite(string $html): string
    {
        $prefix = $this->routePrefix();

        if ($prefix === '') {
            return $html;
        }

        return preg_replace_callback(
            '/\b([A-Za-z_:][-A-Za-z0-9_:.]*)=(["\'])(\/(?!\/)[^"\']*)\2/',
            fn (array $matches): string => $matches[1].'='.$matches[2].$this->rewriteUrl($matches[3], $prefix).$matches[2],
            $html,
        ) ?? $html;
    }

    private function routePrefix(): string
    {
        $path = config('inkstone.routes.path', 'docs');
        $path = trim(is_string($path) ? $path : 'docs', '/');

        return $path !== '' ? '/'.$path : '';
    }

    private function rewriteUrl(string $url, string $prefix): string
    {
        if ($url === $prefix || str_starts_with($url, $prefix.'/')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $url;
        }

        $candidate = ltrim($path, '/');

        if ($this->files->resolve($candidate) === null) {
            return $url;
        }

        $suffix = substr($url, strlen($path));

        return $prefix.($path === '/' ? '' : $path).$suffix;
    }
}

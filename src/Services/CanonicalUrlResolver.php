<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Illuminate\Contracts\Container\Container;
use Inkstone\Support\UrlBuilder;
use RuntimeException;
use Throwable;

final class CanonicalUrlResolver
{
    private const DOCUMENTATION_ROUTE = 'inkstone.docs';

    public function __construct(private readonly Container $container) {}

    public function documentUrl(string $url): string
    {
        $url = trim($url);
        $this->assertValidDocumentUrl($url);

        if ($this->isAbsolute($url)) {
            return $url;
        }

        $configuredBase = $this->configuredSiteBase();
        $canonicalBase = $this->documentationBaseUrl();

        if ($canonicalBase === '') {
            return $url !== '' ? $url : '/';
        }

        if (! $this->isAbsolute($canonicalBase)) {
            if ($url === '' || $url === '/') {
                return $configuredBase !== '' ? $canonicalBase : '/';
            }

            return $url;
        }

        if ($url === '' || $url === '/') {
            return $canonicalBase;
        }

        $canonicalPath = (string) parse_url($canonicalBase, PHP_URL_PATH);
        $urlPath = (string) parse_url($url, PHP_URL_PATH);

        if ($this->pathIsWithinBase($urlPath, $canonicalPath)) {
            return $this->origin($canonicalBase).'/'.ltrim($url, '/');
        }

        return rtrim($canonicalBase, '/').'/'.ltrim($url, '/');
    }

    public function sitemapUrl(): string
    {
        $configuredBase = $this->configuredSiteBase();

        if ($configuredBase === '') {
            $routeUrl = $this->namedRouteUrl('sitemap.xml');

            if ($routeUrl !== null) {
                return $routeUrl;
            }
        }

        return UrlBuilder::to($this->documentationBaseUrl(), 'sitemap.xml');
    }

    public function isAbsolute(string $url): bool
    {
        if ($url === '' || ! $this->hasValidUriSyntax($url)) {
            return false;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = $parts['host'] ?? null;

        if (! is_string($host) || ! $this->isValidHost($host)) {
            return false;
        }

        $port = $parts['port'] ?? null;

        return $port === null || $port >= 1;
    }

    public function warnAboutRelativeSitemapUrls(): void
    {
        $message = 'Inkstone generated relative sitemap URLs because no absolute site or Laravel application URL is available. Configure an absolute inkstone.site.base_url for production.';

        if ($this->container->bound('log')) {
            $this->container->make('log')->warning($message);

            return;
        }

        if (defined('STDERR')) {
            fwrite(STDERR, '[Inkstone] '.$message.PHP_EOL);
        }
    }

    private function documentationBaseUrl(): string
    {
        $configuredBase = $this->configuredSiteBase();

        if ($this->hasHttpScheme($configuredBase) && ! $this->isAbsolute($configuredBase)) {
            throw new RuntimeException('Inkstone site.base_url must be a valid absolute HTTP or HTTPS URL.');
        }

        if ($this->isAbsolute($configuredBase)) {
            return rtrim($configuredBase, '/');
        }

        $applicationBase = $this->applicationBaseUrl();

        if ($configuredBase !== '') {
            return $applicationBase !== null
                ? UrlBuilder::to($applicationBase, $configuredBase)
                : UrlBuilder::normalizeBaseUrl($configuredBase);
        }

        $namedRoute = $this->namedRouteUrl();

        if ($namedRoute !== null) {
            return rtrim($namedRoute, '/');
        }

        return $applicationBase !== null ? $this->configuredRouteBase($applicationBase) : '';
    }

    private function configuredSiteBase(): string
    {
        $base = config('inkstone.site.base_url', '');

        if (is_string($base) && strpbrk($base, '?#') !== false) {
            throw new RuntimeException('Inkstone site.base_url must not contain a query string or fragment.');
        }

        return is_string($base) ? trim($base) : '';
    }

    private function applicationBaseUrl(): ?string
    {
        $configured = config('app.url');

        if (is_string($configured) && $this->isAbsolute($configured)) {
            return $this->absoluteBase($configured, true);
        }

        if ($this->container->bound('url')) {
            try {
                $origin = $this->origin((string) $this->container->make('url')->to('/'));

                if ($this->isAbsolute($origin)) {
                    return $origin;
                }
            } catch (Throwable) {
                // Fall through to the configured application URL.
            }
        }

        return null;
    }

    private function namedRouteUrl(?string $path = null): ?string
    {
        if (! $this->container->bound('router')) {
            return null;
        }

        try {
            $router = $this->container->make('router');
            $routes = $router->getRoutes();
            $routes->refreshNameLookups();
            $route = $routes->getByName(self::DOCUMENTATION_ROUTE);

            if ($route === null) {
                return null;
            }

            $applicationBase = $this->applicationBaseUrl();

            if ($applicationBase === null) {
                return null;
            }

            $routePath = preg_replace('~/?\{inkstonePath\?\}$~', '', $route->uri());

            if (! is_string($routePath) || str_contains($routePath, '{')) {
                return null;
            }

            if ($path !== null) {
                $routePath = trim($routePath, '/').'/'.ltrim($path, '/');
            }

            return UrlBuilder::to($this->routeBase($applicationBase, $route->getDomain()), $routePath);
        } catch (Throwable) {
            return null;
        }
    }

    private function configuredRouteBase(string $applicationBase): string
    {
        return UrlBuilder::to($this->routeBase($applicationBase), $this->configuredRoutePath());
    }

    private function routeBase(string $applicationBase, ?string $routeDomain = null): string
    {
        $domain = $routeDomain ?? config('inkstone.routes.domain');

        if (is_string($domain) && trim($domain) !== '' && ! str_contains($domain, '{')) {
            $scheme = (string) parse_url($applicationBase, PHP_URL_SCHEME);
            $path = (string) parse_url($applicationBase, PHP_URL_PATH);
            $applicationBase = $scheme.'://'.trim($domain, '/').($path !== '/' ? rtrim($path, '/') : '');
        }

        return $applicationBase;
    }

    private function configuredRoutePath(): string
    {
        $path = config('inkstone.routes.path', 'docs');

        return trim(is_string($path) ? $path : 'docs', '/');
    }

    private function pathIsWithinBase(string $path, string $basePath): bool
    {
        $path = '/'.trim($path, '/');
        $basePath = '/'.trim($basePath, '/');

        if ($basePath === '/') {
            return true;
        }

        return $path === $basePath || str_starts_with($path, $basePath.'/');
    }

    private function origin(string $url): string
    {
        if (preg_match('~^(https?://[^/?#]+)~i', $url, $matches) !== 1) {
            return '';
        }

        return rtrim($matches[1], '/');
    }

    private function absoluteBase(string $url, bool $includePath): string
    {
        $base = $this->origin($url);

        if (! $includePath) {
            return $base;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return $base.($path !== '/' ? rtrim($path, '/') : '');
    }

    private function assertValidDocumentUrl(string $url): void
    {
        if (! $this->hasValidUriSyntax($url) || ($this->hasHttpScheme($url) && ! $this->isAbsolute($url))) {
            throw new RuntimeException('Inkstone cannot generate sitemap.xml from a malformed HTTP or HTTPS URL.');
        }
    }

    private function hasValidUriSyntax(string $url): bool
    {
        if (preg_match('/[\x00-\x20\x7f-\xff<>"{}|\\\\^`]/', $url) === 1) {
            return false;
        }

        return preg_match('/%(?![0-9a-f]{2})/i', $url) !== 1;
    }

    private function hasHttpScheme(string $url): bool
    {
        return preg_match('/^https?:/i', $url) === 1;
    }

    private function isValidHost(string $host): bool
    {
        $unwrappedHost = str_starts_with($host, '[') && str_ends_with($host, ']')
            ? substr($host, 1, -1)
            : $host;

        if (filter_var($unwrappedHost, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}

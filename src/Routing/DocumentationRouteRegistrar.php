<?php

declare(strict_types=1);

namespace Inkstone\Routing;

use Illuminate\Support\Facades\Route;
use Inkstone\Http\Controllers\ServeGeneratedDocumentationController;

final class DocumentationRouteRegistrar
{
    public function register(): void
    {
        $attributes = [];

        $domain = $this->configuredDomain();

        if ($domain !== null) {
            $attributes['domain'] = $domain;
        }

        $prefix = $this->configuredPath();

        if ($prefix !== '') {
            $attributes['prefix'] = $prefix;
        }

        $middleware = $this->configuredMiddleware();

        if ($middleware !== []) {
            $attributes['middleware'] = $middleware;
        }

        Route::group($attributes, static function (): void {
            Route::get('{inkstonePath?}', ServeGeneratedDocumentationController::class)
                ->where('inkstonePath', '.*')
                ->name('inkstone.docs');
        });
    }

    private function configuredDomain(): ?string
    {
        $domain = config('inkstone.routes.domain');

        if (! is_string($domain)) {
            return null;
        }

        $domain = trim($domain);

        return $domain !== '' ? $domain : null;
    }

    private function configuredPath(): string
    {
        $path = config('inkstone.routes.path', 'docs');

        return trim(is_string($path) ? $path : 'docs', '/');
    }

    /**
     * @return list<string>
     */
    private function configuredMiddleware(): array
    {
        $middleware = config('inkstone.routes.middleware', []);

        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        if (! is_array($middleware)) {
            return [];
        }

        return array_values(array_filter(
            $middleware,
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));
    }
}

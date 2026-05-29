<?php

declare(strict_types=1);

namespace Inkstone\Console;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class StandaloneApplication extends Container implements Application
{
    public function __construct(private readonly string $basePath) {}

    public function version(): string
    {
        return 'Inkstone';
    }

    public function runningInConsole(): bool
    {
        return true;
    }

    public function runningUnitTests(): bool
    {
        return false;
    }

    public function hasDebugModeEnabled(): bool
    {
        return false;
    }

    public function maintenanceMode(): MaintenanceMode
    {
        return new class implements MaintenanceMode
        {
            public function activate(array $payload): void {}

            public function deactivate(): void {}

            public function active(): bool
            {
                return false;
            }

            public function data(): array
            {
                return [];
            }
        };
    }

    public function isDownForMaintenance(): bool
    {
        return false;
    }

    public function registerConfiguredProviders(): void {}

    public function register($provider, $force = false): ServiceProvider
    {
        $instance = $provider instanceof ServiceProvider
            ? $provider
            : $this->resolveProvider($provider);
        $instance->register();

        if (method_exists($instance, 'boot')) {
            $instance->boot();
        }

        return $instance;
    }

    public function registerDeferredProvider($provider, $service = null): void {}

    public function resolveProvider($provider): ServiceProvider
    {
        if (! is_subclass_of($provider, ServiceProvider::class)) {
            throw new RuntimeException('Invalid service provider ['.$provider.'].');
        }

        return new $provider($this);
    }

    public function boot(): void {}

    public function booting($callback): void {}

    public function booted($callback): void {}

    public function bootstrapWith(array $bootstrappers): void {}

    public function getLocale(): string
    {
        return 'en';
    }

    public function getNamespace(): string
    {
        return 'App\\';
    }

    public function getProviders($provider): array
    {
        return [];
    }

    public function hasBeenBootstrapped(): bool
    {
        return true;
    }

    public function loadDeferredProviders(): void {}

    public function setLocale($locale): void {}

    public function shouldSkipMiddleware(): bool
    {
        return true;
    }

    public function terminating($callback): Application
    {
        return $this;
    }

    public function terminate(): void {}

    public function environment(...$environments): string|bool
    {
        if ($environments === []) {
            return 'production';
        }

        return in_array('production', $environments, true);
    }

    public function basePath($path = ''): string
    {
        return $this->join($this->basePath, (string) $path);
    }

    public function path($path = ''): string
    {
        return $this->join($this->basePath('app'), (string) $path);
    }

    public function configPath($path = ''): string
    {
        return $this->join($this->basePath('config'), (string) $path);
    }

    public function resourcePath($path = ''): string
    {
        return $this->join($this->basePath('resources'), (string) $path);
    }

    public function publicPath($path = ''): string
    {
        return $this->join($this->basePath('public'), (string) $path);
    }

    public function storagePath($path = ''): string
    {
        return $this->join($this->basePath('storage'), (string) $path);
    }

    public function databasePath($path = ''): string
    {
        return $this->join($this->basePath('database'), (string) $path);
    }

    public function bootstrapPath($path = ''): string
    {
        return $this->join($this->basePath('bootstrap'), (string) $path);
    }

    public function langPath($path = ''): string
    {
        return $this->join($this->basePath('lang'), (string) $path);
    }

    private function join(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        return rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }
}

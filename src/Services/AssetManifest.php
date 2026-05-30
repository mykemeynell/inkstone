<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Inkstone\Support\UrlBuilder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class AssetManifest
{
    /** @var array<string, array{file?: string}>|null */
    private ?array $manifest = null;

    public function enabled(): bool
    {
        if (! (bool) config('inkstone.build.asset_hashing', true)) {
            return false;
        }

        if (! is_file($this->manifestPath())) {
            return false;
        }

        if (! $this->fresh()) {
            return false;
        }

        return true;
    }

    public function fresh(): bool
    {
        $manifestPath = $this->manifestPath();
        $distPath = $this->distPath();

        if (! is_dir($distPath) || ! is_file($manifestPath)) {
            return false;
        }

        $manifestMtime = filemtime($manifestPath);

        if ($manifestMtime === false) {
            return false;
        }

        $sourceDirs = [
            dirname(__DIR__, 2).'/resources/css',
            dirname(__DIR__, 2).'/resources/js',
        ];

        foreach ($sourceDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                if ($file->getMTime() > $manifestMtime) {
                    return false;
                }
            }
        }

        return true;
    }

    public function distPath(): string
    {
        $path = config('inkstone.build.assets.dist_path');

        return is_string($path) && $path !== ''
            ? $path
            : dirname(__DIR__, 2).'/resources/dist';
    }

    public function manifestPath(): string
    {
        $path = config('inkstone.build.assets.manifest_path');

        return is_string($path) && $path !== ''
            ? $path
            : rtrim($this->distPath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.vite'.DIRECTORY_SEPARATOR.'manifest.json';
    }

    public function url(string $entry, string $fallback, string $baseUrl): string
    {
        if (! $this->enabled()) {
            return UrlBuilder::to($baseUrl, $fallback);
        }

        $file = $this->manifest()[$entry]['file'] ?? null;

        return UrlBuilder::to($baseUrl, is_string($file) && $file !== '' ? $file : $fallback);
    }

    /**
     * @return array<string, array{file?: string}>
     */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $contents = file_get_contents($this->manifestPath());
        $decoded = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($decoded)) {
            return $this->manifest = [];
        }

        /** @var array<string, array{file?: string}> $manifest */
        $manifest = array_filter($decoded, static fn (mixed $entry): bool => is_array($entry));

        return $this->manifest = $manifest;
    }
}

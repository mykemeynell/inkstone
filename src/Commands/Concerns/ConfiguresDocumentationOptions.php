<?php

declare(strict_types=1);

namespace Inkstone\Commands\Concerns;

use Inkstone\Support\UrlBuilder;

trait ConfiguresDocumentationOptions
{
    private function applyDocumentationOptions(): void
    {
        $configPath = $this->documentationOption('config');

        if (is_string($configPath) && $configPath !== '') {
            $path = $this->absolutePath($configPath);

            if (is_file($path)) {
                $loaded = require $path;

                if (is_array($loaded)) {
                    config()->set('inkstone', array_replace_recursive((array) config('inkstone', []), $loaded));
                }
            }
        }

        $source = $this->documentationOption('source');

        if (is_string($source) && $source !== '') {
            config()->set('inkstone.docs_path', $this->absolutePath($source));
        }

        $output = $this->documentationOption('output');

        if (is_string($output) && $output !== '') {
            config()->set('inkstone.output_path', $this->absolutePath($output));
        }

        $baseUrl = $this->documentationOption('base-url');

        if (is_string($baseUrl) && $baseUrl !== '') {
            config()->set('inkstone.site.base_url', UrlBuilder::normalizeBaseUrl($baseUrl));
        }
    }

    private function absolutePath(string $path): string
    {
        if ($path === '') {
            return base_path();
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function documentationOption(string $name): mixed
    {
        if (! $this->getDefinition()->hasOption($name)) {
            return null;
        }

        return $this->option($name);
    }
}

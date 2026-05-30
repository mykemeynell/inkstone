<?php

declare(strict_types=1);

namespace Inkstone\Services;

final class SearchDriverConfig
{
    public function name(): string
    {
        $name = config('inkstone.search.driver');

        if (! is_string($name) || $name === '') {
            throw new \RuntimeException('Search driver not configured.');
        }

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $config = config("inkstone.search.drivers.{$this->name()}.config", []);

        return is_array($config) ? $config : [];
    }

    public function indexPath(): string
    {
        $path = $this->config()['index_path'] ?? config('inkstone.search.index_path', 'search-index.json');

        return is_string($path) && $path !== '' ? $path : 'search-index.json';
    }

    public function driverClass(): string
    {
        $driverClass = config("inkstone.search.drivers.{$this->name()}.driver");

        if (! is_string($driverClass) || ! class_exists($driverClass)) {
            throw new \RuntimeException("Search driver class [{$driverClass}] does not exist.");
        }

        return $driverClass;
    }

    /**
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        $parameters = [
            'maxContentLength' => (int) config('inkstone.search.max_content_length', 5000),
        ];

        foreach ($this->config() as $key => $value) {
            $parameters[str($key)->camel()->toString()] = $value;
        }

        return $parameters;
    }
}

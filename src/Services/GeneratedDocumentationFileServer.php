<?php

declare(strict_types=1);

namespace Inkstone\Services;

final class GeneratedDocumentationFileServer
{
    public function resolve(string $requestPath): ?string
    {
        $root = $this->outputRoot();

        if ($root === null) {
            return null;
        }

        $path = $this->normalizeRequestPath($requestPath);

        if ($path === null) {
            return null;
        }

        foreach ($this->candidatePaths($path) as $candidate) {
            $resolved = $this->resolveCandidate($root, $candidate);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private function outputRoot(): ?string
    {
        $path = config('inkstone.output_path');

        if (! is_string($path)) {
            return null;
        }

        $root = realpath($path);

        return is_string($root) && is_dir($root) ? $root : null;
    }

    private function normalizeRequestPath(string $path): ?string
    {
        if (str_contains($path, "\0")) {
            return null;
        }

        $path = trim(str_replace('\\', '/', rawurldecode($path)), '/');

        if (str_contains($path, "\0")) {
            return null;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $path): array
    {
        if ($path === '') {
            return ['index.html'];
        }

        return [
            $path,
            $path.'/index.html',
        ];
    }

    private function resolveCandidate(string $root, string $candidate): ?string
    {
        $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        $resolved = realpath($path);

        if (! is_string($resolved) || ! is_file($resolved)) {
            return null;
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($resolved, $root) ? $resolved : null;
    }
}

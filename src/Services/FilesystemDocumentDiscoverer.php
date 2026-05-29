<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Inkstone\Contracts\DocumentDiscoverer;
use Inkstone\DTOs\Document;
use Inkstone\Support\Slugger;
use Inkstone\Support\UrlBuilder;
use Symfony\Component\Finder\Finder;

final class FilesystemDocumentDiscoverer implements DocumentDiscoverer
{
    private Slugger $slugger;

    /**
     * @param  list<string>  $ignoredDirectories
     */
    public function __construct(
        private readonly string $docsPath,
        private readonly string $baseUrl = '',
        private readonly bool $prettyUrls = true,
        private readonly array $ignoredDirectories = [],
    ) {
        $this->slugger = new Slugger;
    }

    public function discover(?string $path = null): array
    {
        $root = $path ?? $this->docsPath;

        if (! is_dir($root)) {
            return [];
        }

        $finder = Finder::create()
            ->files()
            ->in($root)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->name(['*.md', '*.markdown'])
            ->exclude(array_values(array_unique(array_merge([
                'vendor',
                'node_modules',
            ], $this->ignoredDirectories))))
            ->sortByName();

        $documents = [];

        foreach ($finder as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $slug = $this->slugFromRelativePath($relativePath);

            $documents[] = new Document(
                sourcePath: $file->getRealPath() ?: $file->getPathname(),
                relativePath: $relativePath,
                slug: $slug,
                url: $this->urlFromSlug($slug),
                markdown: $file->getContents(),
            );
        }

        return $documents;
    }

    private function slugFromRelativePath(string $relativePath): string
    {
        $path = preg_replace('/\.(md|markdown)$/i', '', $relativePath) ?? $relativePath;
        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $path)), static fn (string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return '';
        }

        $last = strtolower((string) end($segments));

        if ($last === 'readme' || $last === 'index') {
            array_pop($segments);
        }

        return implode('/', array_map(fn (string $segment): string => $this->slugger->slug($segment), $segments));
    }

    private function urlFromSlug(string $slug): string
    {
        if ($slug === '') {
            return UrlBuilder::to($this->baseUrl);
        }

        return UrlBuilder::to($this->baseUrl, $this->prettyUrls ? $slug : $slug.'.html');
    }
}

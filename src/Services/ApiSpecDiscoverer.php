<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Inkstone\Contracts\DocumentDiscoverer;
use Inkstone\DTOs\Document;
use Inkstone\Support\Slugger;

final class ApiSpecDiscoverer implements DocumentDiscoverer
{
    private Slugger $slugger;

    /**
     * @param  list<string>  $specFilenames
     */
    public function __construct(
        private readonly string $docsPath,
        private readonly array $specFilenames,
        private readonly ?string $specPath = null,
        private readonly string $baseUrl = '',
        private readonly string $basePath = 'api',
        private readonly bool $prettyUrls = true,
    ) {
        $this->slugger = new Slugger;
    }

    public function discover(?string $path = null): array
    {
        $root = $path ?? $this->specPath ?? $this->docsPath;

        if (! is_dir($root)) {
            return [];
        }

        $documents = [];

        foreach ($this->specFilenames as $filename) {
            $specPath = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

            if (! is_file($specPath)) {
                continue;
            }

            $documents[] = $this->buildDocument($specPath);
        }

        return $documents;
    }

    private function buildDocument(string $specPath): Document
    {
        $factory = new ApiSpecDocumentFactory;
        $apiData = $factory->parse($specPath);

        $relativePath = 'api/'.basename($specPath);
        $slug = $this->basePath.'/'.$this->slugger->slug(pathinfo($specPath, PATHINFO_FILENAME));
        $url = $this->buildUrl($slug);

        return new Document(
            sourcePath: $specPath,
            relativePath: $relativePath,
            slug: $slug,
            url: $url,
            markdown: '',
            metadata: [
                '_api_spec' => true,
                '_api' => $apiData,
                'title' => $apiData['title'],
            ],
        );
    }

    private function buildUrl(string $slug): string
    {
        $baseUrl = rtrim($this->baseUrl, '/');

        if ($slug === '') {
            return $baseUrl !== '' ? $baseUrl : '/';
        }

        $path = $this->prettyUrls ? $slug : $slug.'.html';

        return $baseUrl !== '' ? $baseUrl.'/'.$path : '/'.$path;
    }
}

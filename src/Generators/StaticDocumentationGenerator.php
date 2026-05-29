<?php

declare(strict_types=1);

namespace Inkstone\Generators;

use Inkstone\Contracts\DocumentDiscoverer;
use Inkstone\Contracts\DocumentRenderer;
use Inkstone\Contracts\MarkdownParser;
use Inkstone\Contracts\NavigationBuilder;
use Inkstone\Contracts\SearchIndexer;
use Inkstone\Contracts\StaticSiteGenerator;
use Inkstone\DTOs\Document;
use Inkstone\Pipelines\TransformerPipeline;
use Inkstone\Services\FileSystemWriter;
use Inkstone\Support\UrlBuilder;

final class StaticDocumentationGenerator implements StaticSiteGenerator
{
    public function __construct(
        private readonly DocumentDiscoverer $discoverer,
        private readonly MarkdownParser $parser,
        private readonly TransformerPipeline $transformers,
        private readonly NavigationBuilder $navigation,
        private readonly DocumentRenderer $renderer,
        private readonly SearchIndexer $search,
        private readonly FileSystemWriter $writer,
    ) {}

    public function build(): array
    {
        $outputPath = (string) config('inkstone.output_path');
        $prettyUrls = (bool) config('inkstone.build.pretty_urls', true);

        if ((bool) config('inkstone.build.clean_output_before_build', true)) {
            $this->writer->cleanDirectory($outputPath);
        } else {
            $this->writer->ensureDirectory($outputPath);
        }

        $this->discoverSourceBrandAssets($outputPath);

        $documents = array_map(
            fn (Document $document): Document => $this->transformers->process($this->parser->parse($document)),
            $this->discoverer->discover(),
        );

        $pages = [];

        foreach ($documents as $document) {
            $navigation = $this->navigation->build($documents, $document);
            $path = $this->writer->outputPathFor($document, $outputPath, $prettyUrls);
            $page = $this->renderer->render($document, $documents, $navigation, $path);
            $this->writer->write($path, $page->html);
            $pages[] = $page;
        }

        $this->writeSearchIndex($documents, $outputPath);
        $this->writeStaticMetadata($documents, $outputPath);
        $this->copyAssets($outputPath);

        return $pages;
    }

    /**
     * @param  list<Document>  $documents
     */
    private function writeSearchIndex(array $documents, string $outputPath): void
    {
        if (! (bool) config('inkstone.search.enabled', true)) {
            return;
        }

        $entries = array_map(
            static fn ($entry): array => $entry->toArray(),
            $this->search->index($documents),
        );

        $indexPath = trim((string) config('inkstone.search.index_path', 'search-index.json'), '/');
        $this->writer->write(
            rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$indexPath,
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]',
        );
    }

    /**
     * @param  list<Document>  $documents
     */
    private function writeStaticMetadata(array $documents, string $outputPath): void
    {
        if ((bool) config('inkstone.build.generate_sitemap', true)) {
            $base = (string) config('inkstone.site.base_url', '');
            $urls = array_map(static fn (Document $document): string => '  <url><loc>'.e(UrlBuilder::to($base, $document->slug)).'</loc></url>', $documents);
            $this->writer->write($outputPath.'/sitemap.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n".implode("\n", $urls)."\n</urlset>\n");
        }

        if ((bool) config('inkstone.build.generate_robots_txt', true)) {
            $this->writer->write($outputPath.'/robots.txt', "User-agent: *\nAllow: /\n");
        }
    }

    private function copyAssets(string $outputPath): void
    {
        $paths = [
            __DIR__.'/../../resources/css',
            __DIR__.'/../../resources/js',
        ];

        foreach ((array) config('inkstone.assets.additional_paths', []) as $path) {
            if (is_string($path)) {
                $paths[] = $path;
            }
        }

        $this->writer->copyAssets($paths, $outputPath.'/assets');
    }

    private function discoverSourceBrandAssets(string $outputPath): void
    {
        $sourcePath = (string) config('inkstone.docs_path');

        if (! is_dir($sourcePath)) {
            return;
        }

        if (! is_string(config('inkstone.site.favicon')) || config('inkstone.site.favicon') === '') {
            $favicon = $this->firstExisting($sourcePath, ['favicon.ico', 'favicon.svg', 'favicon.png']);

            if ($favicon !== null) {
                $target = basename($favicon);
                $this->writer->copyFile($favicon, rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$target);
                config()->set('inkstone.site.favicon', $this->assetUrl($target));
            }
        }

        if (! is_string(config('inkstone.site.logo')) || config('inkstone.site.logo') === '') {
            $logo = $this->firstExisting($sourcePath, ['logo.svg', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp']);

            if ($logo !== null) {
                $target = 'assets/'.basename($logo);
                $this->writer->copyFile($logo, rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $target));
                config()->set('inkstone.site.logo', $this->assetUrl($target));
            }
        }
    }

    /**
     * @param  list<string>  $filenames
     */
    private function firstExisting(string $directory, array $filenames): ?string
    {
        foreach ($filenames as $filename) {
            $path = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function assetUrl(string $path): string
    {
        return UrlBuilder::to((string) config('inkstone.site.base_url', ''), $path);
    }
}

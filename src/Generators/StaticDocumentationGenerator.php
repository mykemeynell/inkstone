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
use Inkstone\DTOs\NavigationItem;
use Inkstone\Pipelines\TransformerPipeline;
use Inkstone\Services\AssetManifest;
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
        private readonly AssetManifest $assets,
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

        $navigationTree = $this->navigation->build($documents, null);
        $sectionMap = $this->buildSectionMap($navigationTree);

        foreach ($documents as $document) {
            $navigation = $this->navigation->build($documents, $document);
            $path = $this->writer->outputPathFor($document, $outputPath, $prettyUrls);
            $page = $this->renderer->render($document, $documents, $navigation, $path);
            $this->writer->write($path, $page->html);
            $pages[] = $page;
        }

        $this->writeSearchIndex($documents, $outputPath, $sectionMap);
        $this->writeStaticMetadata($documents, $outputPath);
        $this->copyAssets($outputPath);

        return $pages;
    }

    /**
     * @param  list<Document>  $documents
     * @param  array<string, string>  $sections  url => section name
     */
    private function writeSearchIndex(array $documents, string $outputPath, array $sections = []): void
    {
        if (! (bool) config('inkstone.search.enabled', true)) {
            return;
        }

        $index = $this->search->index($documents, $sections);

        $this->search->save($index, $outputPath);
    }

    /**
     * @param  list<NavigationItem>  $navigation
     * @return array<string, string>
     */
    private function buildSectionMap(array $navigation): array
    {
        $map = [];

        foreach ($navigation as $item) {
            if ($item->group !== null) {
                $map[$item->url] = $item->group;
            }

            if ($item->children !== []) {
                $map += $this->buildSectionMap($item->children);
            }
        }

        return $map;
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
        if ($this->assets->enabled()) {
            $this->writer->copyBuiltAssets($this->assets->distPath(), $outputPath);
            $paths = [];
        } else {
            $paths = [
                __DIR__.'/../../resources/css',
                __DIR__.'/../../resources/js',
            ];
        }

        foreach ((array) config('inkstone.build.assets.additional_paths', []) as $path) {
            if (is_string($path)) {
                $paths[] = $path;
            }
        }

        $this->writer->copyAssets($paths, $outputPath.'/assets');

        if ((bool) config('inkstone.search.enabled', true)) {
            $searchDriver = (string) config('inkstone.search.driver', 'json');
            $this->writer->copyFile(
                __DIR__.'/../../resources/js/search-drivers/'.$searchDriver.'.js',
                $outputPath.'/assets/js/search-driver.js'
            );
        }
    }

    private function discoverSourceBrandAssets(string $outputPath): void
    {
        $sourcePath = (string) config('inkstone.source_path');

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

        $this->discoverLogos($sourcePath, $outputPath);
    }

    private function discoverLogos(string $sourcePath, string $outputPath): void
    {
        $logo = config('inkstone.site.logo');
        $alreadyConfigured = is_string($logo)
            || (is_array($logo) && ($logo['light'] !== null || $logo['dark'] !== null));

        $extensions = ['svg', 'png', 'jpg', 'jpeg', 'webp'];

        $lightFile = $this->firstExistingByExtensions($sourcePath, 'logo', $extensions);
        $darkFile = $this->firstExistingByExtensions($sourcePath, 'logo-dark', $extensions);

        $lightUrl = null;
        $darkUrl = null;

        if ($lightFile !== null) {
            $target = 'assets/'.basename($lightFile);
            $this->writer->copyFile($lightFile, rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $target));
            $lightUrl = $this->assetUrl($target);
        }

        if ($darkFile !== null) {
            $target = 'assets/'.basename($darkFile);
            $this->writer->copyFile($darkFile, rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $target));
            $darkUrl = $this->assetUrl($target);
        }

        // Dark falls back to light when no dark-specific file exists
        if ($darkUrl === null) {
            $darkUrl = $lightUrl;
        }

        if (! $alreadyConfigured) {
            config()->set('inkstone.site.logo', [
                'light' => $lightUrl,
                'dark' => $darkUrl,
            ]);
        }
    }

    /**
     * @param  list<string>  $extensions
     */
    private function firstExistingByExtensions(string $directory, string $basename, array $extensions): ?string
    {
        return $this->firstExisting(
            $directory,
            array_map(static fn (string $ext): string => $basename.'.'.$ext, $extensions),
        );
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

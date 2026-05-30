<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;
use Inkstone\Search\AlgoliaSearchIndexer;
use Inkstone\Search\JsonSearchIndexer;
use Inkstone\Search\LunrSearchIndexer;
use Inkstone\Search\SearchEntryBuilder;
use Inkstone\Search\TypesenseSearchIndexer;
use Inkstone\Services\FileSystemWriter;
use Inkstone\Services\SearchDriverConfig;
use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class SearchIndexerTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/search-driver-test');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_it_indexes_titles_headings_and_content(): void
    {
        $document = new Document(
            sourcePath: 'docs/readme.md',
            relativePath: 'README.md',
            slug: '',
            url: '/docs',
            html: '<h1>Intro</h1><p>Searchable content</p>',
            metadata: ['title' => 'Introduction'],
            headings: [new Heading(1, 'Intro', 'intro', 0)],
        );

        $entries = $this->indexer()->index([$document]);

        $this->assertSame('Introduction', $entries[0]->title);
        $this->assertSame('/docs', $entries[0]->url);
        $this->assertStringContainsString('Searchable content', $entries[0]->content);
        $this->assertSame('Intro', $entries[0]->headings[0]['text']);
    }

    public function test_it_truncates_large_content_to_the_configured_maximum(): void
    {
        $document = new Document(
            sourcePath: 'docs/readme.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<h1>Intro</h1><p>abcdefghijklmnopqrstuvwxyz</p>',
            metadata: ['title' => 'Introduction'],
        );

        $entries = $this->indexer(maxContentLength: 12)->index([$document]);

        $this->assertLessThanOrEqual(12, strlen($entries[0]->content));
    }

    public function test_heading_anchor_is_not_included_in_indexed_text(): void
    {
        $document = new Document(
            sourcePath: 'docs/configuration.md',
            relativePath: 'configuration.md',
            slug: 'configuration',
            url: '/configuration',
            html: '<h1 id="configuration">Configuration<a href="#configuration" class="heading-anchor" aria-label="Copy link to Configuration" title="Copy link" data-inkstone-heading-copy="configuration">#</a></h1><p>Inkstone works without configuration.</p>',
            metadata: ['title' => 'Configuration'],
            headings: [new Heading(1, 'Configuration', 'configuration', 0)],
        );

        $entries = $this->indexer()->index([$document]);

        $this->assertStringNotContainsString('#', $entries[0]->content);
        $this->assertStringNotContainsString('#', $entries[0]->excerpt);
        $this->assertStringContainsStringIgnoringCase('configuration', $entries[0]->content);
    }

    public function test_excerpt_does_not_start_with_duplicate_page_title(): void
    {
        $document = new Document(
            sourcePath: 'docs/readme.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<h1>Configuration</h1><p>Inkstone works without configuration.</p>',
            metadata: ['title' => 'Configuration'],
            headings: [new Heading(1, 'Configuration', 'configuration', 0)],
        );

        $entries = $this->indexer()->index([$document]);

        $this->assertStringStartsWith('Inkstone', $entries[0]->excerpt);
        $this->assertStringStartsWith('Inkstone', $entries[0]->content);
    }

    public function test_logo_svg_remains_searchable_in_content(): void
    {
        $document = new Document(
            sourcePath: 'docs/readme.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p>Place your logo.svg in the assets directory.</p>',
            metadata: ['title' => 'Introduction'],
        );

        $entries = $this->indexer()->index([$document]);

        $this->assertStringContainsString('logo.svg', $entries[0]->content);
        $this->assertStringContainsString('logo.svg', $entries[0]->excerpt);
    }

    public function test_search_driver_config_resolves_driver_parameters(): void
    {
        config()->set('inkstone.search.driver', 'json');
        config()->set('inkstone.search.max_content_length', 5000);
        config()->set('inkstone.search.drivers.json.config.index_path', 'search/custom-index.json');

        $config = new SearchDriverConfig;

        $this->assertSame(JsonSearchIndexer::class, $config->driverClass());
        $this->assertSame('search/custom-index.json', $config->indexPath());
        $this->assertSame([
            'maxContentLength' => 5000,
            'indexPath' => 'search/custom-index.json',
        ], $config->parameters());
    }

    public function test_lunr_driver_saves_lunr_shaped_documents(): void
    {
        $driver = new LunrSearchIndexer('lunr-docs.json', 5000, app(FileSystemWriter::class), new SearchEntryBuilder);
        $entries = $driver->index([$this->searchableDocument()]);

        $driver->save($entries, $this->outputPath);

        $payload = json_decode(file_get_contents($this->outputPath.'/lunr-docs.json') ?: '', true);

        $this->assertSame('docs', $payload['documents'][0]['id']);
        $this->assertSame('/docs', $payload['documents'][0]['url']);
        $this->assertStringContainsString('Searchable content', $payload['documents'][0]['content']);
        $this->assertSame('Intro', $payload['documents'][0]['headings'][0]['text']);
    }

    public function test_algolia_driver_instantiates_with_proper_config(): void
    {
        $driver = new AlgoliaSearchIndexer(
            appId: 'APPID',
            apiKey: 'KEY',
            indexName: 'docs',
            maxContentLength: 5000,
            entries: new SearchEntryBuilder
        );

        $this->assertInstanceOf(AlgoliaSearchIndexer::class, $driver);
    }

    public function test_typesense_driver_instantiates_with_proper_config(): void
    {
        $driver = new TypesenseSearchIndexer(
            server: ['nodes' => []],
            maxContentLength: 5000,
            entries: new SearchEntryBuilder
        );

        $this->assertInstanceOf(TypesenseSearchIndexer::class, $driver);
    }

    private function indexer(string $indexPath = 'search-index.json', int $maxContentLength = 5000): JsonSearchIndexer
    {
        return new JsonSearchIndexer($indexPath, $maxContentLength, app(FileSystemWriter::class), new SearchEntryBuilder);
    }

    private function searchableDocument(): Document
    {
        return new Document(
            sourcePath: 'docs/readme.md',
            relativePath: 'README.md',
            slug: '',
            url: '/docs',
            html: '<h1>Intro</h1><p>Searchable content</p>',
            metadata: ['title' => 'Introduction'],
            headings: [new Heading(1, 'Intro', 'intro', 0)],
        );
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;
use Inkstone\Search\JsonSearchIndexer;
use Inkstone\Tests\TestCase;

final class SearchIndexerTest extends TestCase
{
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

        $entries = (new JsonSearchIndexer)->index([$document]);

        $this->assertSame('Introduction', $entries[0]->title);
        $this->assertSame('/docs', $entries[0]->url);
        $this->assertStringContainsString('Searchable content', $entries[0]->content);
        $this->assertSame('Intro', $entries[0]->headings[0]['text']);
    }
}

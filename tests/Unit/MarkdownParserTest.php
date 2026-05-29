<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Parsers\CommonMarkMarkdownParser;
use Inkstone\Tests\TestCase;

final class MarkdownParserTest extends TestCase
{
    public function test_it_extracts_frontmatter_headings_and_renders_commonmark(): void
    {
        $document = new Document(
            sourcePath: 'docs/installation.md',
            relativePath: 'installation.md',
            slug: 'installation',
            url: '/docs/installation',
            markdown: "---\ntitle: Installation\norder: 1\n---\n# Install\n\n- [x] Task\n\n| A | B |\n| - | - |\n| 1 | 2 |\n",
        );

        $parsed = (new CommonMarkMarkdownParser)->parse($document);

        $this->assertSame('Installation', $parsed->metadata['title']);
        $this->assertSame(1, $parsed->metadata['order']);
        $this->assertSame('Install', $parsed->headings[0]->text);
        $this->assertStringContainsString('<table>', $parsed->html);
        $this->assertStringNotContainsString('title: Installation', $parsed->html);
    }
}

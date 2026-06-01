<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\ExternalLinkTransformer;

final class ExternalLinkTransformerTest extends TestCase
{
    public function test_it_adds_target_and_rel_to_http_links(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="https://example.com">Example</a><a href="http://example.com">HTTP</a></p>',
        );

        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertStringContainsString('href="https://example.com" target="_blank" rel="noopener noreferrer"', $document->html);
        $this->assertStringContainsString('href="http://example.com" target="_blank" rel="noopener noreferrer"', $document->html);
    }

    public function test_it_merges_existing_rel_without_duplicating(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="https://example.com" rel="nofollow">Example</a></p>',
        );

        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $document->html);
    }

    public function test_it_does_not_duplicate_noopener_or_noreferrer(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="https://example.com" rel="noopener noreferrer">Example</a></p>',
        );

        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertStringContainsString('rel="noopener noreferrer"', $document->html);
        $this->assertStringNotContainsString('noopener noreferrer noopener', $document->html);
        $this->assertStringNotContainsString('noopener noreferrer noreferrer', $document->html);
    }

    public function test_it_handles_multiple_spaces_in_rel(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="https://example.com" rel="  noopener   noreferrer  ">Example</a></p>',
        );

        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertStringContainsString('rel="noopener noreferrer"', $document->html);
    }

    public function test_it_does_not_modify_non_http_links(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="/internal">Internal</a><a href="mailto:hello@example.com">Email</a><a href="#section">Section</a></p>',
        );

        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertStringContainsString('href="/internal"', $document->html);
        $this->assertStringNotContainsString('target="_blank"', $document->html);
    }

    public function test_it_handles_empty_html(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '',
        );

        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertSame('', $document->html);
    }
}

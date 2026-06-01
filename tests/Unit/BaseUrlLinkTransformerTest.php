<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\BaseUrlLinkTransformer;

final class BaseUrlLinkTransformerTest extends TestCase
{
    public function test_it_prepends_base_url_to_root_relative_links(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="/getting-started/installation">Installation</a><a href="/getting-started/standalone-usage">Standalone</a></p>',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('href="/inkstone/getting-started/installation"', $document->html);
        $this->assertStringContainsString('href="/inkstone/getting-started/standalone-usage"', $document->html);
    }

    public function test_it_prepends_base_url_to_root_relative_image_src(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<img src="/assets/logo.png">',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('src="/inkstone/assets/logo.png"', $document->html);
    }

    public function test_it_does_not_modify_links_when_base_url_is_empty(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="/getting-started/installation">Installation</a>',
        );

        $document = $this->transformer('')->transform($document);

        $this->assertStringContainsString('href="/getting-started/installation"', $document->html);
    }

    public function test_it_does_not_modify_links_when_base_url_is_slash(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="/getting-started/installation">Installation</a>',
        );

        $document = $this->transformer('/')->transform($document);

        $this->assertStringContainsString('href="/getting-started/installation"', $document->html);
    }

    public function test_it_does_not_modify_protocol_relative_urls(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="//cdn.example.com/script.js">Script</a><img src="//cdn.example.com/logo.png">',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('href="//cdn.example.com/script.js"', $document->html);
        $this->assertStringContainsString('src="//cdn.example.com/logo.png"', $document->html);
    }

    public function test_it_does_not_modify_fragment_only_links(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="#intro">Intro</a>',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('href="#intro"', $document->html);
    }

    public function test_it_does_not_modify_absolute_urls(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="https://example.com">Example</a>',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('href="https://example.com"', $document->html);
    }

    public function test_it_does_not_modify_relative_links_without_leading_slash(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="../configuration.md">Config</a><a href="getting-started/installation">Install</a>',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('href="../configuration.md"', $document->html);
        $this->assertStringContainsString('href="getting-started/installation"', $document->html);
    }

    public function test_it_handles_links_with_fragments_and_query_strings(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="/getting-started/installation#intro">Install</a><a href="/configuration?ref=home">Config</a>',
        );

        $document = $this->transformer('/inkstone')->transform($document);

        $this->assertStringContainsString('href="/inkstone/getting-started/installation#intro"', $document->html);
        $this->assertStringContainsString('href="/inkstone/configuration?ref=home"', $document->html);
    }

    public function test_it_handles_absolute_base_url_with_protocol(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<a href="/getting-started/installation">Install</a>',
        );

        $document = $this->transformer('https://docs.example.com/inkstone')->transform($document);

        $this->assertStringContainsString(
            'href="https://docs.example.com/inkstone/getting-started/installation"',
            $document->html,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function transformer(string $baseUrl = ''): BaseUrlLinkTransformer
    {
        return new BaseUrlLinkTransformer($baseUrl);
    }
}

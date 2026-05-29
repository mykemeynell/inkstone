<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Services\FilesystemDocumentDiscoverer;
use Inkstone\Tests\TestCase;

final class DocumentDiscoveryTest extends TestCase
{
    public function test_it_discovers_markdown_recursively_and_generates_urls(): void
    {
        $discoverer = new FilesystemDocumentDiscoverer(
            docsPath: __DIR__.'/../fixtures',
            baseUrl: '/docs',
            prettyUrls: true,
        );

        $documents = $discoverer->discover();
        $paths = array_map(static fn ($document): string => $document->relativePath, $documents);
        $urls = array_map(static fn ($document): string => $document->url, $documents);

        $this->assertContains('README.md', $paths);
        $this->assertContains('docs/getting-started/installation.md', $paths);
        $this->assertContains('/docs', $urls);
        $this->assertContains('/docs/docs/getting-started/installation', $urls);
        $this->assertNotContains('.DS_Store', $paths);
    }
}

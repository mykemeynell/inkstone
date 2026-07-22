<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Services\ApiSpecDiscoverer;
use Inkstone\Tests\TestCase;

final class ApiSpecDiscovererTest extends TestCase
{
    public function test_it_discovers_openapi_yaml_in_fixtures(): void
    {
        $discoverer = new ApiSpecDiscoverer(
            docsPath: __DIR__.'/../fixtures/docs',
            specFilenames: ['openapi.yaml'],
            baseUrl: '/docs',
            basePath: 'api',
            prettyUrls: true,
        );

        $documents = $discoverer->discover();

        $this->assertCount(1, $documents);

        $document = $documents[0];

        $this->assertSame('api/openapi', $document->slug);
        $this->assertSame('/docs/api/openapi', $document->url);
        $this->assertStringEndsWith('openapi.yaml', $document->sourcePath);
        $this->assertSame('api/openapi.yaml', $document->relativePath);
        $this->assertSame('', $document->markdown);
        $this->assertTrue($document->metadata['_api_spec']);
        $this->assertSame('Users API', $document->metadata['title']);
        $this->assertArrayHasKey('_api', $document->metadata);
    }

    public function test_it_returns_empty_when_no_spec_file_exists(): void
    {
        $discoverer = new ApiSpecDiscoverer(
            docsPath: __DIR__.'/../fixtures/docs',
            specFilenames: ['nonexistent.yaml'],
        );

        $documents = $discoverer->discover();

        $this->assertCount(0, $documents);
    }

    public function test_it_returns_empty_when_directory_does_not_exist(): void
    {
        $discoverer = new ApiSpecDiscoverer(
            docsPath: __DIR__.'/../fixtures/nonexistent',
            specFilenames: ['openapi.yaml'],
        );

        $documents = $discoverer->discover();

        $this->assertCount(0, $documents);
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Contracts\DocumentDiscoverer;
use Inkstone\DTOs\Document;
use Inkstone\Services\CompositeDocumentDiscoverer;
use Inkstone\Tests\TestCase;

final class CompositeDocumentDiscovererTest extends TestCase
{
    public function test_it_merges_results_from_multiple_discoverers(): void
    {
        $discoverer1 = new class implements DocumentDiscoverer
        {
            public function discover(?string $path = null): array
            {
                return [new Document(
                    sourcePath: 'a.md',
                    relativePath: 'a.md',
                    slug: 'a',
                    url: '/a',
                )];
            }
        };

        $discoverer2 = new class implements DocumentDiscoverer
        {
            public function discover(?string $path = null): array
            {
                return [new Document(
                    sourcePath: 'b.md',
                    relativePath: 'b.md',
                    slug: 'b',
                    url: '/b',
                )];
            }
        };

        $composite = new CompositeDocumentDiscoverer([$discoverer1, $discoverer2]);
        $documents = $composite->discover();

        $this->assertCount(2, $documents);
        $this->assertSame('a.md', $documents[0]->relativePath);
        $this->assertSame('b.md', $documents[1]->relativePath);
    }

    public function test_it_returns_empty_when_no_discoverers(): void
    {
        $composite = new CompositeDocumentDiscoverer([]);
        $documents = $composite->discover();

        $this->assertCount(0, $documents);
    }

    public function test_it_returns_empty_when_discoverer_returns_empty(): void
    {
        $discoverer = new class implements DocumentDiscoverer
        {
            public function discover(?string $path = null): array
            {
                return [];
            }
        };

        $composite = new CompositeDocumentDiscoverer([$discoverer]);
        $documents = $composite->discover();

        $this->assertCount(0, $documents);
    }

    public function test_it_forwards_path_to_child_discoverers(): void
    {
        $discoverer = new class implements DocumentDiscoverer
        {
            public ?string $receivedPath = null;

            public function discover(?string $path = null): array
            {
                $this->receivedPath = $path;

                return [];
            }
        };

        $composite = new CompositeDocumentDiscoverer([$discoverer]);
        $composite->discover('/custom/path');

        $this->assertSame('/custom/path', $discoverer->receivedPath);
    }
}

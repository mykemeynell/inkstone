<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Services\NavigationBuilder;
use Inkstone\Tests\TestCase;

final class NavigationBuilderTest extends TestCase
{
    public function test_it_builds_ordered_nested_navigation(): void
    {
        $documents = [
            new Document('docs/configuration/cache.md', 'configuration/cache.md', 'configuration/cache', '/docs/configuration/cache', metadata: ['title' => 'Cache', 'order' => 2]),
            new Document('docs/readme.md', 'README.md', '', '/docs', metadata: ['title' => 'Introduction', 'order' => 1]),
            new Document('docs/configuration/index.md', 'configuration/index.md', 'configuration', '/docs/configuration', metadata: ['title' => 'Configuration', 'order' => 3]),
        ];

        $navigation = (new NavigationBuilder)->build($documents, $documents[0]);

        $this->assertSame('Introduction', $navigation[0]->title);
        $this->assertSame('Configuration', $navigation[1]->title);
        $this->assertSame('#', $navigation[1]->url);
        $this->assertSame('Overview', $navigation[1]->children[0]->title);
        $this->assertSame('/docs/configuration', $navigation[1]->children[0]->url);
        $this->assertSame('Cache', $navigation[1]->children[1]->title);
        $this->assertTrue($navigation[1]->children[1]->active);
    }

    public function test_nested_order_only_applies_inside_its_group(): void
    {
        $documents = [
            new Document('docs/configuration/database.md', 'configuration/database.md', 'configuration/database', '/docs/configuration/database', metadata: ['title' => 'Database', 'order' => 2]),
            new Document('docs/configuration/cache.md', 'configuration/cache.md', 'configuration/cache', '/docs/configuration/cache', metadata: ['title' => 'Cache', 'order' => 1]),
            new Document('docs/getting-started/installation.md', 'getting-started/installation.md', 'getting-started/installation', '/docs/getting-started/installation', metadata: ['title' => 'Installation', 'order' => 1]),
            new Document('docs/configuration/index.md', 'configuration/index.md', 'configuration', '/docs/configuration', metadata: ['title' => 'Configuration', 'order' => 2]),
            new Document('docs/getting-started/index.md', 'getting-started/index.md', 'getting-started', '/docs/getting-started', metadata: ['title' => 'Getting Started', 'order' => 1]),
        ];

        $navigation = (new NavigationBuilder)->build($documents);

        $this->assertSame('Getting Started', $navigation[0]->title);
        $this->assertSame('Configuration', $navigation[1]->title);
        $this->assertSame('Cache', $navigation[1]->children[1]->title);
        $this->assertSame('Database', $navigation[1]->children[2]->title);
    }
}

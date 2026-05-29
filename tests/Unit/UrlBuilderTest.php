<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Support\UrlBuilder;
use PHPUnit\Framework\TestCase;

final class UrlBuilderTest extends TestCase
{
    public function test_it_builds_urls_for_the_served_root(): void
    {
        $this->assertSame('/', UrlBuilder::to(''));
        $this->assertSame('/assets/css/inkstone.css', UrlBuilder::to('', 'assets/css/inkstone.css'));
        $this->assertSame('/search-index.json', UrlBuilder::to('/', '/search-index.json'));
    }

    public function test_it_builds_urls_for_a_subdirectory_base_url(): void
    {
        $this->assertSame('/docs', UrlBuilder::to('/docs'));
        $this->assertSame('/docs/assets/css/inkstone.css', UrlBuilder::to('docs/', 'assets/css/inkstone.css'));
    }

    public function test_it_preserves_absolute_base_urls(): void
    {
        $this->assertSame(
            'https://example.com/docs/assets/css/inkstone.css',
            UrlBuilder::to('https://example.com/docs/', 'assets/css/inkstone.css'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Extensions\SitemapExtension;
use Inkstone\Support\InkstoneConfigMerger;
use PHPUnit\Framework\TestCase;

final class InkstoneConfigMergerTest extends TestCase
{
    public function test_an_explicit_extension_list_replaces_the_default_while_other_sections_merge(): void
    {
        $merged = InkstoneConfigMerger::merge(
            [
                'extensions' => [SitemapExtension::class],
                'site' => ['name' => 'Inkstone', 'base_url' => '/docs'],
            ],
            [
                'extensions' => [],
                'site' => ['name' => 'Application Docs'],
            ],
        );

        $this->assertSame([], $merged['extensions']);
        $this->assertSame([
            'name' => 'Application Docs',
            'base_url' => '/docs',
        ], $merged['site']);
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ThemeCssTest extends TestCase
{
    public function test_code_blocks_have_stable_font_sizing_when_they_scroll(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/inkstone.css') ?: '';

        $this->assertStringContainsString('.inkstone-prose pre {', $css);
        $this->assertStringContainsString('font-size: .875rem;', $css);
        $this->assertStringContainsString('text-size-adjust: 100%;', $css);
        $this->assertStringContainsString('.inkstone-prose pre code {', $css);
        $this->assertStringContainsString('font-size: inherit;', $css);
    }

    public function test_mobile_navigation_has_a_scrollable_viewport_constrained_region(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/inkstone.css') ?: '';

        $this->assertStringContainsString('height: calc(100dvh - 66px);', $css);
        $this->assertStringContainsString('max-height: calc(100dvh - 66px);', $css);
        $this->assertStringContainsString('overflow-y: auto;', $css);
        $this->assertStringContainsString('-webkit-overflow-scrolling: touch;', $css);
        $this->assertStringContainsString('overscroll-behavior: contain;', $css);
    }
}

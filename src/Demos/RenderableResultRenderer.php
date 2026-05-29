<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Inkstone\Contracts\DemoResultRenderer;
use Stringable;

final class RenderableResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Htmlable || $value instanceof View || $value instanceof Stringable;
    }

    public function render(mixed $value): string
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        if ($value instanceof View) {
            return $value->render();
        }

        return e((string) $value);
    }
}

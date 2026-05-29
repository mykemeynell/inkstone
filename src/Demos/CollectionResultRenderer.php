<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Illuminate\Support\Collection;
use Inkstone\Contracts\DemoResultRenderer;

final class CollectionResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Collection;
    }

    public function render(mixed $value): string
    {
        return '<pre><code class="language-json">'.e($value->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</code></pre>';
    }
}

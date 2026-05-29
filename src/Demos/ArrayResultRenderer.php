<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Inkstone\Contracts\DemoResultRenderer;

final class ArrayResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return is_array($value);
    }

    public function render(mixed $value): string
    {
        return '<pre><code class="language-json">'.e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]').'</code></pre>';
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Illuminate\Database\Eloquent\Model;
use Inkstone\Contracts\DemoResultRenderer;

final class ModelResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Model;
    }

    public function render(mixed $value): string
    {
        return '<pre><code class="language-json">'.e($value->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</code></pre>';
    }
}

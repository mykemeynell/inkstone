<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Inkstone\Contracts\DemoResultRenderer;
use Throwable;

final class ExceptionResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Throwable;
    }

    public function render(mixed $value): string
    {
        return '<pre><code class="language-text">'.e($value::class.': '.$value->getMessage()).'</code></pre>';
    }
}

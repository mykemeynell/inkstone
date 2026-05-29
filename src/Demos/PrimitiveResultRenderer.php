<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use BackedEnum;
use Inkstone\Contracts\DemoResultRenderer;
use UnitEnum;

final class PrimitiveResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return $value === null || is_scalar($value) || $value instanceof UnitEnum;
    }

    public function render(mixed $value): string
    {
        if ($value instanceof UnitEnum) {
            $value = $value instanceof BackedEnum ? $value->value : $value->name;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        if ($value === null) {
            $value = 'null';
        }

        return '<pre><code class="language-text">'.e((string) $value).'</code></pre>';
    }
}

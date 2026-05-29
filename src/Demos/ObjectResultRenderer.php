<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Inkstone\Contracts\DemoResultRenderer;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

final class ObjectResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return is_object($value);
    }

    public function render(mixed $value): string
    {
        $cloner = new VarCloner;
        $dumper = new HtmlDumper;

        return $dumper->dump($cloner->cloneVar($value), true);
    }
}

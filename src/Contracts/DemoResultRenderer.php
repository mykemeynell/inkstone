<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

interface DemoResultRenderer
{
    public function supports(mixed $value): bool;

    public function render(mixed $value): string;
}

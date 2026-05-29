<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

use Throwable;

final class DemoResult
{
    public function __construct(
        public readonly DemoBlock $block,
        public readonly bool $successful,
        public readonly mixed $value = null,
        public readonly string $stdout = '',
        public readonly ?Throwable $exception = null,
        public readonly bool $expectedException = false,
    ) {}
}

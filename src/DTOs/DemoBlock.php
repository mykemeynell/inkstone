<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

use Throwable;

final class DemoBlock
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<class-string<Throwable>>  $expectedExceptions
     */
    public function __construct(
        public readonly string $language,
        public readonly string $code,
        public readonly array $metadata = [],
        public readonly array $expectedExceptions = [],
        public readonly bool $voidOutput = false,
    ) {}

    public function expectsException(): bool
    {
        return $this->expectedExceptions !== [];
    }
}

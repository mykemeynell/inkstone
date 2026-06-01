<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final class BrokenLinkReport
{
    public function __construct(
        public readonly string $sourceFile,
        public readonly string $sourceTitle,
        public readonly string $brokenHref,
        public readonly string $reason,
    ) {}
}

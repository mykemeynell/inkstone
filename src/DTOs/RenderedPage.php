<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final class RenderedPage
{
    public function __construct(
        public readonly Document $document,
        public readonly string $html,
        public readonly string $outputPath,
    ) {}
}

<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final class Heading
{
    public function __construct(
        public readonly int $level,
        public readonly string $text,
        public readonly string $id,
        public readonly int $position,
    ) {}

    /**
     * @return array{level: int, text: string, id: string, position: int}
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'text' => $this->text,
            'id' => $this->id,
            'position' => $this->position,
        ];
    }
}

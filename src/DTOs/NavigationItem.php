<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final class NavigationItem
{
    /**
     * @param  list<NavigationItem>  $children
     * @param  list<Heading>  $headings
     */
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly array $children = [],
        public readonly bool $active = false,
        public readonly int $order = PHP_INT_MAX,
        public readonly ?string $sourcePath = null,
        public readonly array $headings = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'active' => $this->active,
            'order' => $this->order,
            'sourcePath' => $this->sourcePath,
            'headings' => array_map(
                static fn (Heading $heading): array => $heading->toArray(),
                $this->headings,
            ),
            'children' => array_map(
                static fn (NavigationItem $item): array => $item->toArray(),
                $this->children,
            ),
        ];
    }
}

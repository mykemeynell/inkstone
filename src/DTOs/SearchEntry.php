<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final class SearchEntry
{
    /**
     * @param  list<array{level: int, text: string, id: string}>  $headings
     */
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $excerpt,
        public readonly string $content,
        public readonly array $headings = [],
        public readonly ?string $section = null,
    ) {}

    /**
     * @return array{title: string, url: string, excerpt: string, content: string, headings: list<array{level: int, text: string, id: string}>, section: string|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'headings' => $this->headings,
            'section' => $this->section,
        ];
    }
}

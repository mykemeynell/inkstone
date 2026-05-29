<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final class Document
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<Heading>  $headings
     * @param  array<string, mixed>  $navigationContext
     */
    public function __construct(
        public readonly string $sourcePath,
        public readonly string $relativePath,
        public readonly string $slug,
        public readonly string $url,
        public readonly string $markdown = '',
        public readonly string $html = '',
        public readonly array $metadata = [],
        public readonly array $headings = [],
        public readonly array $navigationContext = [],
        public readonly mixed $ast = null,
    ) {}

    public function withMarkdown(string $markdown): self
    {
        return new self(
            $this->sourcePath,
            $this->relativePath,
            $this->slug,
            $this->url,
            $markdown,
            $this->html,
            $this->metadata,
            $this->headings,
            $this->navigationContext,
            $this->ast,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<Heading>  $headings
     */
    public function withParsedContent(string $html, array $metadata, array $headings, mixed $ast = null): self
    {
        return new self(
            $this->sourcePath,
            $this->relativePath,
            $this->slug,
            $this->url,
            $this->markdown,
            $html,
            $metadata,
            $headings,
            $this->navigationContext,
            $ast,
        );
    }

    public function withHtml(string $html): self
    {
        return new self(
            $this->sourcePath,
            $this->relativePath,
            $this->slug,
            $this->url,
            $this->markdown,
            $html,
            $this->metadata,
            $this->headings,
            $this->navigationContext,
            $this->ast,
        );
    }

    /**
     * @param  array<string, mixed>  $navigationContext
     */
    public function withNavigationContext(array $navigationContext): self
    {
        return new self(
            $this->sourcePath,
            $this->relativePath,
            $this->slug,
            $this->url,
            $this->markdown,
            $this->html,
            $this->metadata,
            $this->headings,
            $navigationContext,
            $this->ast,
        );
    }

    public function title(): string
    {
        if (is_string($this->metadata['title'] ?? null) && $this->metadata['title'] !== '') {
            return $this->metadata['title'];
        }

        foreach ($this->headings as $heading) {
            if ($heading->level === 1) {
                return $heading->text;
            }
        }

        $basename = pathinfo($this->relativePath, PATHINFO_FILENAME);

        if (strtolower($basename) === 'readme' || strtolower($basename) === 'index') {
            return 'Introduction';
        }

        return str($basename)->replace(['-', '_'], ' ')->title()->toString();
    }

    public function order(): int
    {
        $order = $this->metadata['order'] ?? PHP_INT_MAX;

        return is_numeric($order) ? (int) $order : PHP_INT_MAX;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sourcePath' => $this->sourcePath,
            'relativePath' => $this->relativePath,
            'slug' => $this->slug,
            'url' => $this->url,
            'metadata' => $this->metadata,
            'headings' => array_map(
                static fn (Heading $heading): array => $heading->toArray(),
                $this->headings,
            ),
        ];
    }
}

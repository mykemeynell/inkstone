<?php

declare(strict_types=1);

namespace Inkstone\Search;

use Inkstone\Contracts\SearchIndexer;
use Inkstone\Services\FileSystemWriter;

final class LunrSearchIndexer implements SearchIndexer
{
    public function __construct(
        private readonly ?string $indexPath,
        private readonly int $maxContentLength,
        private readonly FileSystemWriter $writer,
        private readonly SearchEntryBuilder $entries,
    ) {}

    public function index(array $documents, array $sections = []): array
    {
        return $this->entries->build($documents, $this->maxContentLength, $sections);
    }

    public function save(array $index, string $outputPath): void
    {
        $documents = array_map(static fn ($entry): array => [
            'id' => trim($entry->url, '/') !== '' ? trim($entry->url, '/') : 'index',
            'title' => $entry->title,
            'url' => $entry->url,
            'excerpt' => $entry->excerpt,
            'content' => $entry->content,
            'headings' => $entry->headings,
            'section' => $entry->section,
        ], $index);

        $this->writer->write(
            rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.trim($this->indexPath ?? 'lunr-index.json', '/'),
            json_encode(['documents' => $documents], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{"documents":[]}',
        );
    }
}

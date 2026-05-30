<?php

declare(strict_types=1);

namespace Inkstone\Search;

use Inkstone\Contracts\SearchIndexer;
use Inkstone\Services\FileSystemWriter;

final class JsonSearchIndexer implements SearchIndexer
{
    public function __construct(
        private readonly ?string $indexPath,
        private readonly int $maxContentLength,
        private readonly FileSystemWriter $writer,
        private readonly SearchEntryBuilder $entries,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function index(array $documents, array $sections = []): array
    {
        return $this->entries->build($documents, $this->maxContentLength, $sections);
    }

    /**
     * {@inheritDoc}
     */
    public function save(array $index, string $outputPath): void
    {
        $indexPath = trim($this->indexPath ?? 'search-index.json', '/');

        $this->writer->write(
            rtrim($outputPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$indexPath,
            json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]',
        );
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\SearchEntry;

interface SearchIndexer
{
    /**
     * @param  list<Document>  $documents
     * @param  array<string, string>  $sections  url => section name
     * @return list<SearchEntry>
     */
    public function index(array $documents, array $sections = []): array;

    /**
     * Saves the built search index.
     *
     * @param  list<SearchEntry>  $index
     */
    public function save(array $index, string $outputPath): void;
}

<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\SearchEntry;

interface SearchIndexer
{
    /**
     * @param  list<Document>  $documents
     * @return list<SearchEntry>
     */
    public function index(array $documents): array;
}

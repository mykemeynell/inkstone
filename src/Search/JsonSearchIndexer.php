<?php

declare(strict_types=1);

namespace Inkstone\Search;

use Inkstone\Contracts\SearchIndexer;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;
use Inkstone\DTOs\SearchEntry;

final class JsonSearchIndexer implements SearchIndexer
{
    public function index(array $documents): array
    {
        $maxLength = (int) config('inkstone.search.max_content_length', 5000);

        return array_map(function (Document $document) use ($maxLength): SearchEntry {
            $content = trim(preg_replace('/\s+/', ' ', strip_tags($document->html)) ?? '');
            $content = mb_substr($content, 0, $maxLength);

            return new SearchEntry(
                title: $document->title(),
                url: $document->url,
                excerpt: mb_substr($content, 0, 240),
                content: $content,
                headings: array_map(static fn (Heading $heading): array => [
                    'level' => $heading->level,
                    'text' => $heading->text,
                    'id' => $heading->id,
                ], $document->headings),
            );
        }, $documents);
    }
}

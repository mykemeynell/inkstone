<?php

declare(strict_types=1);

namespace Inkstone\Search;

use Inkstone\Contracts\SearchIndexer;

final class AlgoliaSearchIndexer implements SearchIndexer
{
    public function __construct(
        private readonly string $appId,
        private readonly string $apiKey,
        private readonly string $indexName,
        private readonly int $maxContentLength,
        private readonly SearchEntryBuilder $entries,
    ) {}

    public function index(array $documents, array $sections = []): array
    {
        return $this->entries->build($documents, $this->maxContentLength, $sections);
    }

    public function save(array $index, string $outputPath): void
    {
        $records = array_map(static fn ($entry): array => [
            'objectID' => trim($entry->url, '/') !== '' ? trim($entry->url, '/') : 'index',
            'title' => $entry->title,
            'url' => $entry->url,
            'excerpt' => $entry->excerpt,
            'content' => $entry->content,
            'headings' => $entry->headings,
            'section' => $entry->section,
        ], $index);

        if ($this->appId === '' || $this->apiKey === '' || $this->indexName === '') {
            return;
        }

        $this->pushToAlgolia($records);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function pushToAlgolia(array $records): void
    {
        $url = "https://{$this->appId}.algolia.net/1/indexes/{$this->indexName}/batch";

        $requests = array_map(static fn ($record): array => [
            'action' => 'updateObject',
            'body' => $record,
        ], $records);

        $payload = json_encode(['requests' => $requests]);

        $ch = curl_init($url);

        if ($ch === false) {
            return;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Algolia-API-Key: {$this->apiKey}",
            "X-Algolia-Application-Id: {$this->appId}",
            'Content-Type: application/json',
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}

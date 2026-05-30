<?php

declare(strict_types=1);

namespace Inkstone\Search;

use Inkstone\Contracts\SearchIndexer;

final class TypesenseSearchIndexer implements SearchIndexer
{
    public function __construct(
        private readonly array $server,
        private readonly int $maxContentLength,
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
            'headings' => array_map(static fn (array $heading): string => $heading['text'], $entry->headings),
            'section' => $entry->section,
        ], $index);

        if (($this->server['api_key'] ?? '') === '' || ($this->server['collection_name'] ?? '') === '') {
            return;
        }

        $this->pushToTypesense($documents);
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     */
    private function pushToTypesense(array $documents): void
    {
        $node = $this->server['nodes'][0] ?? [];
        $host = $node['host'] ?? 'localhost';
        $port = $node['port'] ?? '8108';
        $protocol = $node['protocol'] ?? 'http';
        $apiKey = $this->server['api_key'] ?? '';
        $collection = $this->server['collection_name'] ?? '';

        $url = "{$protocol}://{$host}:{$port}/collections/{$collection}/documents/import?action=upsert";

        $payload = implode("\n", array_map(static fn ($doc) => json_encode($doc), $documents));

        $ch = curl_init($url);

        if ($ch === false) {
            return;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-TYPESENSE-API-KEY: {$apiKey}",
            'Content-Type: text/plain',
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}

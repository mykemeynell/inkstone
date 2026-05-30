<?php

declare(strict_types=1);

namespace Inkstone\Search;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;
use Inkstone\DTOs\SearchEntry;

final class SearchEntryBuilder
{
    /**
     * @param  list<Document>  $documents
     * @param  array<string, string>  $sections  url => section name
     * @return list<SearchEntry>
     */
    public function build(array $documents, int $maxContentLength, array $sections = []): array
    {
        return array_map(function (Document $document) use ($maxContentLength, $sections): SearchEntry {
            $content = $document->html;

            // Remove heading anchor elements before tag stripping
            // These are <a class="heading-anchor">#</a> added by HeadingAnchorTransformer
            $content = preg_replace(
                '/<a\b[^>]*\bclass\s*=\s*"[^"]*\bheading-anchor\b[^"]*"[^>]*>\s*#\s*<\/a>/i',
                '',
                $content,
            ) ?? '';

            // Replace tags with spaces to avoid joining words
            $content = preg_replace('/<[^>]+>/', ' ', $content) ?? '';

            // Decode HTML entities
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Normalize whitespace
            $content = trim(preg_replace('/\s+/', ' ', $content) ?? '');

            // Trim a leading duplicate of the document title from content
            // so the search-result heading (title) isn't repeated in the preview
            $title = $document->title();
            if ($title !== '' && str_starts_with($content, $title)) {
                $content = trim(mb_substr($content, mb_strlen($title)));
            }

            $content = mb_substr($content, 0, $maxContentLength);

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
                section: $sections[$document->url] ?? null,
            );
        }, $documents);
    }
}

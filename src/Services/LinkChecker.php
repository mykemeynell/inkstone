<?php

declare(strict_types=1);

namespace Inkstone\Services;

use DOMElement;
use DOMXPath;
use Inkstone\DTOs\BrokenLinkReport;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;

final class LinkChecker
{
    /**
     * @param  list<Document>  $documents
     * @return list<BrokenLinkReport>
     */
    public function check(array $documents): array
    {
        $reports = [];

        $urlMap = $this->buildUrlMap($documents);
        $idMap = $this->buildIdMap($documents);

        foreach ($documents as $document) {
            if ($document->html === '') {
                continue;
            }

            $fragment = HtmlDocument::fromFragment($document->html);
            $links = $fragment->xpath()->query('//a[@href]');

            foreach ($links as $link) {
                if (! $link instanceof DOMElement) {
                    continue;
                }

                $report = $this->validateLink(
                    $link->getAttribute('href'),
                    $document,
                    $urlMap,
                    $idMap,
                );

                if ($report !== null) {
                    $reports[] = $report;
                }
            }
        }

        return $reports;
    }

    /**
     * @param  list<Document>  $documents
     * @return array<string, Document>
     */
    private function buildUrlMap(array $documents): array
    {
        $map = [];

        foreach ($documents as $document) {
            $map[$document->url] = $document;
        }

        return $map;
    }

    /**
     * @param  list<Document>  $documents
     * @return array<string, list<string>>
     */
    private function buildIdMap(array $documents): array
    {
        $map = [];

        foreach ($documents as $document) {
            $ids = [];

            if ($document->html !== '') {
                $fragment = HtmlDocument::fromFragment($document->html);
                $nodes = $fragment->xpath()->query('//*[@id]');

                foreach ($nodes as $node) {
                    if ($node instanceof DOMElement) {
                        $id = $node->getAttribute('id');

                        if ($id !== '') {
                            $ids[] = $id;
                        }
                    }
                }
            }

            $map[$document->url] = $ids;
        }

        return $map;
    }

    /**
     * @param  array<string, Document>  $urlMap
     * @param  array<string, list<string>>  $idMap
     */
    private function validateLink(
        string $href,
        Document $sourceDocument,
        array $urlMap,
        array $idMap,
    ): ?BrokenLinkReport {
        if ($href === '' || str_starts_with($href, '//')) {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) === 1) {
            return null;
        }

        $parts = parse_url($href);
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? rawurldecode($parts['fragment']) : '';

        if ($path === '' || $path === '/') {
            if ($fragment !== '') {
                return $this->validateFragment($fragment, $sourceDocument->url, $sourceDocument, $idMap, $href);
            }

            return null;
        }

        $normalizedPath = rtrim($path, '/');

        $targetDocument = $urlMap[$normalizedPath] ?? $urlMap[$normalizedPath.'/'] ?? null;

        if ($targetDocument === null) {
            return new BrokenLinkReport(
                sourceFile: $sourceDocument->relativePath,
                sourceTitle: $sourceDocument->title(),
                brokenHref: $href,
                reason: "Page not found at '{$normalizedPath}'",
            );
        }

        if ($fragment !== '') {
            return $this->validateFragment($fragment, $targetDocument->url, $sourceDocument, $idMap, $href);
        }

        return null;
    }

    /**
     * @param  array<string, list<string>>  $idMap
     */
    private function validateFragment(
        string $fragment,
        string $targetUrl,
        Document $sourceDocument,
        array $idMap,
        string $originalHref,
    ): ?BrokenLinkReport {
        $ids = $idMap[$targetUrl] ?? [];

        if (! in_array($fragment, $ids, true)) {
            return new BrokenLinkReport(
                sourceFile: $sourceDocument->relativePath,
                sourceTitle: $sourceDocument->title(),
                brokenHref: $originalHref,
                reason: "Anchor '#{$fragment}' not found on page '{$targetUrl}'",
            );
        }

        return null;
    }
}

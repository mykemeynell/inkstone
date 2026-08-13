<?php

declare(strict_types=1);

namespace Inkstone\Extensions;

use DOMDocument;
use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;
use Inkstone\DTOs\Document;
use Inkstone\Services\CanonicalUrlResolver;
use Inkstone\Services\FileSystemWriter;
use RuntimeException;

final class SitemapExtension implements BuildExtension
{
    private const XML_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public function __construct(
        private readonly FileSystemWriter $writer,
        private readonly CanonicalUrlResolver $urls,
        private readonly int $maxUrls = 50_000,
        private readonly int $maxBytes = 50 * 1024 * 1024,
    ) {}

    public function afterBuild(BuildContext $context): void
    {
        if (! (bool) config('inkstone.build.generate_sitemap', true)) {
            return;
        }

        $locations = $this->locations($context->documents);

        if (count($locations) > $this->maxUrls) {
            throw new RuntimeException(sprintf(
                'Inkstone cannot generate sitemap.xml because a sitemap may contain at most %d URLs.',
                $this->maxUrls,
            ));
        }

        if ($this->containsRelativeUrl($locations)) {
            $this->urls->warnAboutRelativeSitemapUrls();
        }

        $xml = $this->toXml($locations);

        if (strlen($xml) > $this->maxBytes) {
            throw new RuntimeException(sprintf(
                'Inkstone cannot generate sitemap.xml because an uncompressed sitemap may contain at most %d bytes.',
                $this->maxBytes,
            ));
        }

        $this->writer->write($context->outputPath.'/sitemap.xml', $xml);
    }

    public function url(): string
    {
        return $this->urls->sitemapUrl();
    }

    /**
     * @param  list<Document>  $documents
     * @return list<string>
     */
    private function locations(array $documents): array
    {
        $locations = [];

        foreach ($documents as $document) {
            $location = $this->urls->documentUrl($document->url);
            $locations[$location] = true;
        }

        return array_keys($locations);
    }

    /**
     * @param  list<string>  $locations
     */
    private function containsRelativeUrl(array $locations): bool
    {
        foreach ($locations as $location) {
            if (! $this->urls->isAbsolute($location)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $locations
     */
    private function toXml(array $locations): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $urlset = $xml->createElementNS(self::XML_NAMESPACE, 'urlset');
        $xml->appendChild($urlset);

        foreach ($locations as $location) {
            $url = $xml->createElementNS(self::XML_NAMESPACE, 'url');
            $loc = $xml->createElementNS(self::XML_NAMESPACE, 'loc');
            $loc->appendChild($xml->createTextNode($location));
            $url->appendChild($loc);
            $urlset->appendChild($url);
        }

        $contents = $xml->saveXML();

        if (! is_string($contents)) {
            throw new RuntimeException('Inkstone could not serialize sitemap.xml.');
        }

        return $contents;
    }
}

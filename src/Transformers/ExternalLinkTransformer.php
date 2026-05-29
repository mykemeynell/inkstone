<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMElement;
use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;

final class ExternalLinkTransformer implements Transformer
{
    public function transform(Document $document): Document
    {
        if ($document->html === '') {
            return $document;
        }

        $fragment = HtmlDocument::fromFragment($document->html);
        $links = $fragment->xpath()->query('//a[@href]');

        foreach ($links as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $href = $link->getAttribute('href');

            if (! preg_match('/^https?:\/\//i', $href)) {
                continue;
            }

            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', $this->mergeRel($link->getAttribute('rel')));
        }

        return $document->withHtml($fragment->toHtml());
    }

    private function mergeRel(string $rel): string
    {
        $values = array_filter(preg_split('/\s+/', $rel) ?: []);

        foreach (['noopener', 'noreferrer'] as $value) {
            if (! in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        return implode(' ', $values);
    }
}

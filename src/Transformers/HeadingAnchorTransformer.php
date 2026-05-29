<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMElement;
use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;
use Inkstone\Support\Slugger;

final class HeadingAnchorTransformer implements Transformer
{
    public function transform(Document $document): Document
    {
        if ($document->html === '') {
            return $document;
        }

        $fragment = HtmlDocument::fromFragment($document->html);
        $ids = [];
        $slugger = new Slugger;
        $nodes = $fragment->xpath()->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $text = trim($node->textContent);
            $base = $node->getAttribute('id') ?: $slugger->slug($text);
            $id = $base;
            $suffix = 2;

            while (isset($ids[$id])) {
                $id = $base.'-'.$suffix;
                $suffix++;
            }

            $ids[$id] = true;
            $node->setAttribute('id', $id);

            if ($node->firstElementChild instanceof DOMElement && $node->firstElementChild->getAttribute('class') === 'heading-anchor') {
                continue;
            }

            $anchor = $fragment->document->createElement('a', '#');
            $anchor->setAttribute('href', '#'.$id);
            $anchor->setAttribute('class', 'heading-anchor');
            $anchor->setAttribute('aria-label', 'Copy link to '.$text);
            $anchor->setAttribute('title', 'Copy link');
            $anchor->setAttribute('data-inkstone-heading-copy', $id);
            $node->appendChild($anchor);
        }

        return $document->withHtml($fragment->toHtml());
    }
}

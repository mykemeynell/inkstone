<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMElement;
use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;

final class BaseUrlLinkTransformer implements Transformer
{
    public function __construct(
        private readonly string $baseUrl = '',
    ) {}

    public function transform(Document $document): Document
    {
        if ($document->html === '' || $this->baseUrl === '' || $this->baseUrl === '/') {
            return $document;
        }

        $fragment = HtmlDocument::fromFragment($document->html);

        $links = $fragment->xpath()->query('//a[@href]');
        foreach ($links as $link) {
            if ($link instanceof DOMElement) {
                $this->rewriteAttribute($link, 'href');
            }
        }

        $images = $fragment->xpath()->query('//img[@src]');
        foreach ($images as $image) {
            if ($image instanceof DOMElement) {
                $this->rewriteAttribute($image, 'src');
            }
        }

        return $document->withHtml($fragment->toHtml());
    }

    private function rewriteAttribute(DOMElement $element, string $attribute): void
    {
        $value = $element->getAttribute($attribute);

        if (! $this->isRootRelative($value)) {
            return;
        }

        $element->setAttribute($attribute, $this->baseUrl.$value);
    }

    private function isRootRelative(string $value): bool
    {
        return str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && $value !== '/';
    }
}

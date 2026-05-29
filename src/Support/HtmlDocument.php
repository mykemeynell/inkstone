<?php

declare(strict_types=1);

namespace Inkstone\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class HtmlDocument
{
    private DOMElement $root;

    private function __construct(
        public readonly DOMDocument $document,
        private readonly string $rootId,
    ) {
        $root = $this->document->getElementById($rootId);

        if (! $root instanceof DOMElement) {
            throw new \RuntimeException('Unable to create HTML fragment root.');
        }

        $this->root = $root;
    }

    public static function fromFragment(string $html): self
    {
        $rootId = 'inkstone-fragment-root';
        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="'.$rootId.'">'.$html.'</div></body></html>',
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new self($document, $rootId);
    }

    public function xpath(): DOMXPath
    {
        return new DOMXPath($this->document);
    }

    public function root(): DOMElement
    {
        return $this->root;
    }

    public function toHtml(): string
    {
        $html = '';

        foreach ($this->root->childNodes as $node) {
            $html .= $this->document->saveHTML($node);
        }

        return $html;
    }
}

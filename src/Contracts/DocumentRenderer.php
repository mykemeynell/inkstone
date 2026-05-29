<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\NavigationItem;
use Inkstone\DTOs\RenderedPage;

interface DocumentRenderer
{
    /**
     * @param  list<Document>  $documents
     * @param  list<NavigationItem>  $navigation
     */
    public function render(Document $document, array $documents, array $navigation, string $outputPath): RenderedPage;
}

<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\NavigationItem;

interface NavigationBuilder
{
    /**
     * @param  list<Document>  $documents
     * @return list<NavigationItem>
     */
    public function build(array $documents, ?Document $activeDocument = null): array;
}

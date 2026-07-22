<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Inkstone\Contracts\DocumentDiscoverer;

final class CompositeDocumentDiscoverer implements DocumentDiscoverer
{
    /**
     * @param  list<DocumentDiscoverer>  $discoverers
     */
    public function __construct(private readonly array $discoverers) {}

    public function discover(?string $path = null): array
    {
        $documents = [];

        foreach ($this->discoverers as $discoverer) {
            array_push($documents, ...$discoverer->discover($path));
        }

        return $documents;
    }
}

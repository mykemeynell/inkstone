<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;

interface DocumentDiscoverer
{
    /**
     * @return list<Document>
     */
    public function discover(?string $path = null): array;
}

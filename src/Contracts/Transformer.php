<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;

interface Transformer
{
    public function transform(Document $document): Document;
}

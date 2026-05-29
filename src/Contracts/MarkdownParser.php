<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\Document;

interface MarkdownParser
{
    public function parse(Document $document): Document;
}

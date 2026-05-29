<?php

declare(strict_types=1);

namespace Inkstone\Pipelines;

use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;

final class TransformerPipeline
{
    /**
     * @param  list<Transformer>  $transformers
     */
    public function __construct(private readonly array $transformers) {}

    public function process(Document $document): Document
    {
        foreach ($this->transformers as $transformer) {
            $document = $transformer->transform($document);
        }

        return $document;
    }
}

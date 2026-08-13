<?php

declare(strict_types=1);

namespace Inkstone\DTOs;

final readonly class BuildContext
{
    /**
     * @param  list<Document>  $documents
     * @param  list<RenderedPage>  $pages
     */
    public function __construct(
        public array $documents,
        public array $pages,
        public string $outputPath,
    ) {}
}

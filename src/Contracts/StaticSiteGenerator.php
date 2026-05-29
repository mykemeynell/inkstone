<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\RenderedPage;

interface StaticSiteGenerator
{
    /**
     * @return list<RenderedPage>
     */
    public function build(): array;
}

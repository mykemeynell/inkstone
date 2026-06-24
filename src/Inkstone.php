<?php

declare(strict_types=1);

namespace Inkstone;

use Inkstone\Contracts\StaticSiteGenerator;
use Inkstone\DTOs\RenderedPage;
use Inkstone\Routing\DocumentationRouteRegistrar;

final class Inkstone implements StaticSiteGenerator
{
    public function __construct(
        private readonly StaticSiteGenerator $generator,
        private readonly DocumentationRouteRegistrar $routes,
    ) {}

    /**
     * @return list<RenderedPage>
     */
    public function build(): array
    {
        return $this->generator->build();
    }

    public function routes(): void
    {
        $this->routes->register();
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Inkstone\Contracts\DemoResultRenderer;

final class DemoRendererRegistry
{
    /**
     * @var list<DemoResultRenderer>
     */
    private array $renderers;

    public function __construct()
    {
        $this->renderers = [
            new ExceptionResultRenderer,
            new RenderableResultRenderer,
            new PrimitiveResultRenderer,
            new ArrayResultRenderer,
            new CollectionResultRenderer,
            new ModelResultRenderer,
            new ObjectResultRenderer,
        ];
    }

    public function render(mixed $value): string
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($value)) {
                return $renderer->render($value);
            }
        }

        return e((string) $value);
    }
}

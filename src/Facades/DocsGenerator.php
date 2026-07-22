<?php

namespace Inkstone\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static list<\Inkstone\DTOs\RenderedPage> build()
 * @method static void routes()
 * @method static bool docsBuilt()
 */
class DocsGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'inkstone';
    }
}

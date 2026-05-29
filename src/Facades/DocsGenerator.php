<?php

namespace Inkstone\Facades;

use Illuminate\Support\Facades\Facade;

class DocsGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'inkstone';
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Support\Extensions;

final class StandaloneExtensionDependency
{
    public function label(): string
    {
        return 'constructor-injected';
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Support\Extensions;

use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;
use RuntimeException;

final class ThrowingBuildExtension implements BuildExtension
{
    public function afterBuild(BuildContext $context): void
    {
        throw new RuntimeException('The application build extension failed.');
    }
}

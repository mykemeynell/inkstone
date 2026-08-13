<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\BuildContext;

interface BuildExtension
{
    public function afterBuild(BuildContext $context): void;
}

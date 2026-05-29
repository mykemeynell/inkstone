<?php

declare(strict_types=1);

namespace Inkstone\Contracts;

use Inkstone\DTOs\DemoBlock;
use Inkstone\DTOs\DemoResult;

interface DemoRuntime
{
    public function run(DemoBlock $block): DemoResult;
}

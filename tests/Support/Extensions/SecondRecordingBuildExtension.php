<?php

declare(strict_types=1);

namespace Inkstone\Tests\Support\Extensions;

use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;

final class SecondRecordingBuildExtension implements BuildExtension
{
    public function __construct(private readonly ExtensionExecutionRecorder $recorder)
    {
        $this->recorder->recordConstruction('second');
    }

    public function afterBuild(BuildContext $context): void
    {
        $this->recorder->recordExecution('second', $context);
    }
}

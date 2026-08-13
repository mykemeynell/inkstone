<?php

declare(strict_types=1);

namespace Inkstone\Tests\Support\Extensions;

use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;
use Inkstone\DTOs\Document;

final class FirstRecordingBuildExtension implements BuildExtension
{
    public function __construct(private readonly ExtensionExecutionRecorder $recorder)
    {
        $this->recorder->recordConstruction('first');
    }

    public function afterBuild(BuildContext $context): void
    {
        $this->recorder->recordExecution('first', $context);

        file_put_contents(
            $context->outputPath.'/extension-manifest.json',
            json_encode([
                'documents' => array_map(
                    static fn (Document $document): string => $document->url,
                    $context->documents,
                ),
                'pages' => count($context->pages),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }
}

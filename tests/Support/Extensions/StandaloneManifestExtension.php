<?php

declare(strict_types=1);

namespace Inkstone\Tests\Support\Extensions;

use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;
use Inkstone\DTOs\Document;

final class StandaloneManifestExtension implements BuildExtension
{
    public function __construct(private readonly StandaloneExtensionDependency $dependency) {}

    public function afterBuild(BuildContext $context): void
    {
        file_put_contents(
            $context->outputPath.'/standalone-extension.json',
            json_encode([
                'dependency' => $this->dependency->label(),
                'output_path' => $context->outputPath,
                'documents' => array_map(
                    static fn (Document $document): string => $document->url,
                    $context->documents,
                ),
                'page_count' => count($context->pages),
                'core_artifacts' => [
                    'page' => is_file($context->outputPath.'/index.html'),
                    'search' => is_file($context->outputPath.'/search-index.json'),
                    'robots' => is_file($context->outputPath.'/robots.txt'),
                    'assets' => is_file($context->outputPath.'/assets/css/inkstone.css'),
                ],
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }
}

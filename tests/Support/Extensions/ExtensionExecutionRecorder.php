<?php

declare(strict_types=1);

namespace Inkstone\Tests\Support\Extensions;

use Inkstone\DTOs\BuildContext;

final class ExtensionExecutionRecorder
{
    /** @var list<string> */
    public array $constructed = [];

    /** @var list<string> */
    public array $executed = [];

    /** @var list<BuildContext> */
    public array $contexts = [];

    /** @var array<string, array<string, bool>> */
    public array $artifactsAtConstruction = [];

    public function __construct(private readonly string $outputPath) {}

    public function recordConstruction(string $extension): void
    {
        $this->constructed[] = $extension;
        $this->artifactsAtConstruction[$extension] = $this->coreArtifactState();
    }

    public function recordExecution(string $extension, BuildContext $context): void
    {
        $this->executed[] = $extension;
        $this->contexts[] = $context;
    }

    /** @return array<string, bool> */
    private function coreArtifactState(): array
    {
        return [
            'page' => is_file($this->outputPath.'/index.html'),
            'search' => is_file($this->outputPath.'/search-index.json'),
            'robots' => is_file($this->outputPath.'/robots.txt'),
            'assets' => is_file($this->outputPath.'/assets/css/inkstone.css'),
        ];
    }
}

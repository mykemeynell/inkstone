<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class StandaloneCliTest extends TestCase
{
    private string $outputPath;

    private string $packagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagePath = dirname(__DIR__, 2);
        $this->outputPath = $this->packagePath.'/build/standalone-feature';
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_it_builds_docs_without_a_laravel_application(): void
    {
        $process = new Process([
            PHP_BINARY,
            $this->packagePath.'/bin/inkstone',
            'docs:build',
            '--source=stubs/docs',
            '--output=build/standalone-feature',
            '--base-url=/docs',
        ], $this->packagePath);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
        $this->assertFileExists($this->outputPath.'/index.html');
        $this->assertFileExists($this->outputPath.'/configuration/index.html');
        $this->assertFileExists($this->outputPath.'/search-index.json');
    }

    public function test_it_generates_an_ai_prompt_without_a_laravel_application(): void
    {
        $target = $this->outputPath.'/ai-prompt.md';
        $process = new Process([
            PHP_BINARY,
            $this->packagePath.'/bin/inkstone',
            'docs:ai-prompt',
            '--source=stubs/docs',
            '--write=build/standalone-feature/ai-prompt.md',
            '--max-files=4',
        ], $this->packagePath);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
        $this->assertFileExists($target);
        $this->assertStringContainsString('AI Documentation Prompt For Inkstone', file_get_contents($target) ?: '');
    }
}

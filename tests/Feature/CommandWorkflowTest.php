<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class CommandWorkflowTest extends TestCase
{
    private string $docsPath;

    private string $outputPath;

    private string $deployPath;

    private string $aiPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docsPath = base_path('docs-install-test');
        $this->outputPath = base_path('build/clean-test');
        $this->deployPath = base_path('deploy/inkstone');
        $this->aiPath = base_path('inkstone/ai');

        config()->set('inkstone.docs_path', $this->docsPath);
        config()->set('inkstone.output_path', $this->outputPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove([
            $this->docsPath,
            $this->outputPath,
            $this->deployPath,
            base_path('inkstone'),
            base_path('build/.gitignore'),
        ]);

        parent::tearDown();
    }

    public function test_install_creates_starter_docs_without_overwriting_existing_files(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->docsPath);
        file_put_contents($this->docsPath.'/README.md', '# Existing');

        $this->artisan('docs:install')->assertExitCode(0);

        $this->assertSame('# Existing', file_get_contents($this->docsPath.'/README.md'));
        $this->assertFileExists($this->docsPath.'/configuration.md');
        $this->assertFileExists($this->deployPath.'/netlify.toml');
        $this->assertFileExists($this->aiPath.'/documentation-guide.md');
    }

    public function test_clean_removes_only_configured_generated_output(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->outputPath);
        file_put_contents($this->outputPath.'/index.html', 'Generated');

        $this->artisan('docs:clean')->assertExitCode(0);

        $this->assertDirectoryExists($this->outputPath);
        $this->assertFileDoesNotExist($this->outputPath.'/index.html');
    }

    public function test_ai_prompt_command_writes_project_prompt(): void
    {
        $target = base_path('build/clean-test/ai-prompt.md');

        $this->artisan('docs:ai-prompt', [
            '--source' => __DIR__.'/../fixtures',
            '--write' => $target,
            '--max-files' => 5,
        ])->assertExitCode(0);

        $contents = file_get_contents($target) ?: '';

        $this->assertStringContainsString('AI Documentation Prompt For Inkstone', $contents);
        $this->assertStringContainsString('Project File Map', $contents);
        $this->assertStringContainsString('README.md', $contents);
    }
}

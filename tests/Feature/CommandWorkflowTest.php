<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Services\LocalDocumentationServer;
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

        config()->set('inkstone.source_path', $this->docsPath);
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

    public function test_build_returns_failure_for_unexpected_demo_exceptions(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->docsPath);
        file_put_contents($this->docsPath.'/README.md', <<<'MARKDOWN'
# Broken Demo

```demo:php
throw new RuntimeException('Build should fail');
```
MARKDOWN);

        config()->set('inkstone.demos.enabled', true);

        $this->artisan('docs:build')->assertExitCode(1);
    }

    public function test_serve_uses_configured_host_and_port_without_rebuilding_existing_output(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->outputPath);
        file_put_contents($this->outputPath.'/index.html', 'Existing output');

        $server = new class extends LocalDocumentationServer
        {
            /** @var array{root: string, host: string, port: int}|null */
            public ?array $served = null;

            public function serve(string $root, string $host, int $port): int
            {
                $this->served = compact('root', 'host', 'port');

                return 0;
            }
        };

        $this->app->instance(LocalDocumentationServer::class, $server);

        $this->artisan('docs:serve', [
            '--host' => '127.0.0.1',
            '--port' => 8123,
        ])->assertExitCode(0);

        $this->assertSame([
            'root' => $this->outputPath,
            'host' => '127.0.0.1',
            'port' => 8123,
        ], $server->served);
        $this->assertSame('Existing output', file_get_contents($this->outputPath.'/index.html'));
    }

    public function test_serve_watch_builds_and_passes_watch_paths_to_local_server(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->docsPath);
        file_put_contents($this->docsPath.'/README.md', '# Watched Docs');

        $server = new class extends LocalDocumentationServer
        {
            /** @var array{root: string, host: string, port: int, watchPaths: list<string>}|null */
            public ?array $watched = null;

            public function serveWithWatch(string $root, string $host, int $port, array $watchPaths, callable $rebuild): int
            {
                $this->watched = compact('root', 'host', 'port', 'watchPaths');

                return 0;
            }
        };

        $this->app->instance(LocalDocumentationServer::class, $server);

        $this->artisan('docs:serve', [
            '--watch' => true,
            '--host' => '127.0.0.1',
            '--port' => 8124,
        ])->assertExitCode(0);

        $this->assertFileExists($this->outputPath.'/index.html');
        $this->assertSame($this->outputPath, $server->watched['root'] ?? null);
        $this->assertSame('127.0.0.1', $server->watched['host'] ?? null);
        $this->assertSame(8124, $server->watched['port'] ?? null);
        $this->assertContains($this->docsPath, $server->watched['watchPaths'] ?? []);
    }

    public function test_serve_returns_failure_when_required_build_fails(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->docsPath);
        file_put_contents($this->docsPath.'/README.md', <<<'MARKDOWN'
# Broken Demo

```demo:php
throw new RuntimeException('Build should fail');
```
MARKDOWN);

        config()->set('inkstone.demos.enabled', true);

        $server = new class extends LocalDocumentationServer
        {
            public bool $served = false;

            public function serve(string $root, string $host, int $port): int
            {
                $this->served = true;

                return 0;
            }

            public function serveWithWatch(string $root, string $host, int $port, array $watchPaths, callable $rebuild): int
            {
                $this->served = true;

                return 0;
            }
        };

        $this->app->instance(LocalDocumentationServer::class, $server);

        $this->artisan('docs:serve', ['--watch' => true])->assertExitCode(1);

        $this->assertFalse($server->served);
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

<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class DocsBuiltTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/docs-built-test');

        config()->set('inkstone.output_path', $this->outputPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_it_returns_false_when_the_docs_output_path_does_not_exist(): void
    {
        $this->assertFalse(\Inkstone::docsBuilt());
    }

    public function test_it_returns_false_when_the_docs_output_path_has_no_root_index(): void
    {
        $this->writeOutputFile('assets/css/inkstone.css', 'body{}');

        $this->assertFalse(\Inkstone::docsBuilt());
    }

    public function test_it_returns_true_when_the_docs_output_path_has_a_root_index(): void
    {
        $this->writeOutputFile('index.html', '<h1>Generated Docs</h1>');

        $this->assertTrue(\Inkstone::docsBuilt());
    }

    public function test_it_returns_true_after_docs_are_built(): void
    {
        config()->set('inkstone.source_path', __DIR__.'/../fixtures');
        config()->set('inkstone.demos.enabled', false);
        config()->set('inkstone.build.asset_hashing', false);

        $this->artisan('docs:build')->assertExitCode(0);

        $this->assertTrue(\Inkstone::docsBuilt());
    }

    private function writeOutputFile(string $path, string $contents): void
    {
        $target = $this->outputPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

        (new Filesystem)->mkdir(dirname($target));

        file_put_contents($target, $contents);
    }
}

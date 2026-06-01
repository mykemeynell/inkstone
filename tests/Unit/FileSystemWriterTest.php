<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Services\FileSystemWriter;
use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FileSystemWriterTest extends TestCase
{
    private FileSystemWriter $writer;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writer = new FileSystemWriter(new Filesystem);
        $this->tempDir = sys_get_temp_dir().'/inkstone-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->tempDir);
        parent::tearDown();
    }

    public function test_it_refuses_to_clean_an_empty_path(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refusing to clean an unsafe output path.');
        $this->writer->cleanDirectory('');
    }

    public function test_it_refuses_to_clean_the_root_path(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refusing to clean an unsafe output path.');
        $this->writer->cleanDirectory('/');
    }

    public function test_it_refuses_to_clean_a_path_without_build_in_it(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refusing to clean an unsafe output path.');
        $this->writer->cleanDirectory('/tmp/some-random-directory');
    }

    public function test_it_cleans_a_path_containing_build(): void
    {
        mkdir($this->tempDir.'/build/docs', 0777, true);
        file_put_contents($this->tempDir.'/build/docs/index.html', '<html></html>');

        $this->writer->cleanDirectory($this->tempDir.'/build/docs');

        $this->assertDirectoryExists($this->tempDir.'/build/docs');
        $this->assertFileDoesNotExist($this->tempDir.'/build/docs/index.html');
    }

    public function test_it_creates_the_directory_if_it_does_not_exist(): void
    {
        $path = $this->tempDir.'/build/output';

        $this->writer->cleanDirectory($path);

        $this->assertDirectoryExists($path);
    }
}

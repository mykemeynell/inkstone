<?php

declare(strict_types=1);

namespace Inkstone\Services;

use Inkstone\DTOs\Document;
use Symfony\Component\Filesystem\Filesystem;

final class FileSystemWriter
{
    public function __construct(private readonly Filesystem $filesystem) {}

    public function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            $this->filesystem->mkdir($path);
        }
    }

    public function cleanDirectory(string $path): void
    {
        if ($path === '' || $path === '/' || ! str_contains($path, 'build')) {
            throw new \RuntimeException('Refusing to clean an unsafe output path.');
        }

        if (is_dir($path)) {
            $this->filesystem->remove($path);
        }

        $this->ensureDirectory($path);
    }

    public function outputPathFor(Document $document, string $outputRoot, bool $prettyUrls): string
    {
        if (! $prettyUrls) {
            return rtrim($outputRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.($document->slug !== '' ? $document->slug : 'index').'.html';
        }

        $directory = rtrim($outputRoot, DIRECTORY_SEPARATOR);

        if ($document->slug !== '') {
            $directory .= DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $document->slug);
        }

        return $directory.DIRECTORY_SEPARATOR.'index.html';
    }

    public function write(string $path, string $contents): void
    {
        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $contents);
    }

    public function copyFile(string $source, string $target): void
    {
        $this->ensureDirectory(dirname($target));
        $this->filesystem->copy($source, $target, true);
    }

    public function copyBuiltAssets(string $sourceRoot, string $outputRoot): void
    {
        if (! is_dir($sourceRoot)) {
            return;
        }

        $sourceRoot = rtrim($sourceRoot, DIRECTORY_SEPARATOR);
        $outputRoot = rtrim($outputRoot, DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($sourceRoot) + 1);

            if (str_starts_with($relative, '.vite'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $this->copyFile($file->getPathname(), $outputRoot.DIRECTORY_SEPARATOR.$relative);
        }
    }

    /**
     * @param  list<string>  $paths
     */
    public function copyAssets(array $paths, string $outputRoot): void
    {
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $target = rtrim($outputRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename($path);
            $this->filesystem->mirror($path, $target, null, ['override' => true]);
        }
    }
}

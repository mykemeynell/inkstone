<?php

declare(strict_types=1);

namespace Inkstone\Commands;

use Illuminate\Console\Command;
use Inkstone\Commands\Concerns\ConfiguresDocumentationOptions;
use Symfony\Component\Filesystem\Filesystem;

final class InstallCommand extends Command
{
    use ConfiguresDocumentationOptions;

    protected $signature = 'docs:install
        {--source= : Starter documentation target directory}
        {--output= : Static documentation output directory}
        {--base-url= : Base URL used by generated documentation links}
        {--config= : Optional Inkstone PHP config file}
        {--force : Overwrite existing Inkstone files}';

    protected $description = 'Install Inkstone configuration, starter docs, theme assets, and deployment examples.';

    public function handle(Filesystem $filesystem): int
    {
        $this->applyDocumentationOptions();

        $force = (bool) $this->option('force');

        if ($this->getApplication()?->has('vendor:publish') === true) {
            $this->callSilent('vendor:publish', [
                '--tag' => 'inkstone-config',
                '--force' => $force,
            ]);

            $this->callSilent('vendor:publish', [
                '--tag' => 'inkstone-theme',
                '--force' => $force,
            ]);

            $this->callSilent('vendor:publish', [
                '--tag' => 'inkstone-assets',
                '--force' => $force,
            ]);

            $this->callSilent('vendor:publish', [
                '--tag' => 'inkstone-ai',
                '--force' => $force,
            ]);
        } else {
            $this->writeFile($filesystem, base_path('inkstone.php'), file_get_contents(__DIR__.'/../../config/inkstone.php') ?: '', $force);
            $this->installDirectory($filesystem, __DIR__.'/../../resources/views/themes/default', base_path('resources/views/vendor/inkstone/themes/default'), $force);
            $this->installDirectory($filesystem, __DIR__.'/../../resources/css', base_path('resources/inkstone/css'), $force);
            $this->installDirectory($filesystem, __DIR__.'/../../resources/js', base_path('resources/inkstone/js'), $force);
            $this->installDirectory($filesystem, __DIR__.'/../../stubs/ai', base_path('inkstone/ai'), $force);
        }

        $this->installDirectory($filesystem, __DIR__.'/../../stubs/docs', (string) config('inkstone.source_path'), $force);
        $this->installDirectory($filesystem, __DIR__.'/../../stubs/deploy', base_path('deploy/inkstone'), $force);
        $this->writeFile($filesystem, base_path('build/.gitignore'), "*\n!.gitignore\n", $force);

        $this->components->info('Inkstone installed.');

        return self::SUCCESS;
    }

    private function installDirectory(Filesystem $filesystem, string $source, string $target, bool $force): void
    {
        if (! is_dir($source)) {
            return;
        }

        $filesystem->mkdir($target);

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($source) + 1);
            $this->writeFile($filesystem, $target.DIRECTORY_SEPARATOR.$relative, file_get_contents($file->getPathname()) ?: '', $force);
        }
    }

    private function writeFile(Filesystem $filesystem, string $path, string $contents, bool $force): void
    {
        if (file_exists($path) && ! $force) {
            return;
        }

        $filesystem->mkdir(dirname($path));
        file_put_contents($path, $contents);
    }
}

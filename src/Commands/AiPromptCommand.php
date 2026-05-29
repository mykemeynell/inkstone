<?php

declare(strict_types=1);

namespace Inkstone\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

final class AiPromptCommand extends Command
{
    protected $signature = 'docs:ai-prompt
        {--source= : Project source directory to describe}
        {--write= : Optional markdown file to write instead of stdout}
        {--max-files=80 : Maximum project files to list}';

    protected $description = 'Generate a reusable AI prompt for creating project documentation with Inkstone.';

    public function handle(): int
    {
        $source = $this->absolutePath((string) ($this->option('source') ?: base_path()));
        $maxFiles = max(1, (int) $this->option('max-files'));
        $prompt = $this->buildPrompt($source, $maxFiles);
        $writePath = $this->option('write');

        if (is_string($writePath) && $writePath !== '') {
            $path = $this->absolutePath($writePath);

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $prompt);
            $this->components->info("Inkstone AI documentation prompt written to {$path}.");

            return self::SUCCESS;
        }

        $this->line($prompt);

        return self::SUCCESS;
    }

    private function buildPrompt(string $source, int $maxFiles): string
    {
        $template = file_get_contents(__DIR__.'/../../stubs/ai/documentation-guide.md') ?: '';
        $files = $this->projectFiles($source, $maxFiles);

        return strtr($template, [
            '{{ project_path }}' => $source,
            '{{ max_files }}' => (string) $maxFiles,
            '{{ file_map }}' => $files === [] ? '- No files discovered.' : implode("\n", array_map(static fn (string $file): string => "- `{$file}`", $files)),
        ]);
    }

    /**
     * @return list<string>
     */
    private function projectFiles(string $source, int $maxFiles): array
    {
        if (! is_dir($source)) {
            return [];
        }

        $finder = Finder::create()
            ->files()
            ->in($source)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->exclude(['vendor', 'node_modules', 'build', 'storage', '.idea'])
            ->notPath('composer.lock')
            ->notPath('package-lock.json')
            ->sortByName();

        $files = [];

        foreach ($finder as $file) {
            $files[] = str_replace('\\', '/', $file->getRelativePathname());

            if (count($files) >= $maxFiles) {
                break;
            }
        }

        return $files;
    }

    private function absolutePath(string $path): string
    {
        if ($path === '') {
            return base_path();
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}

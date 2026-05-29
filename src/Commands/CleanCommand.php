<?php

declare(strict_types=1);

namespace Inkstone\Commands;

use Illuminate\Console\Command;
use Inkstone\Commands\Concerns\ConfiguresDocumentationOptions;
use Inkstone\Services\FileSystemWriter;

final class CleanCommand extends Command
{
    use ConfiguresDocumentationOptions;

    protected $signature = 'docs:clean
        {--output= : Static documentation output directory}
        {--config= : Optional Inkstone PHP config file}';

    protected $description = 'Remove generated Inkstone documentation output.';

    public function handle(): int
    {
        $this->applyDocumentationOptions();

        $writer = app(FileSystemWriter::class);
        $writer->cleanDirectory((string) config('inkstone.output_path'));
        $this->components->info('Inkstone output cleaned.');

        return self::SUCCESS;
    }
}

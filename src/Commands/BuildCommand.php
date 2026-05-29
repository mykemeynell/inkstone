<?php

declare(strict_types=1);

namespace Inkstone\Commands;

use Illuminate\Console\Command;
use Inkstone\Commands\Concerns\ConfiguresDocumentationOptions;
use Inkstone\Contracts\StaticSiteGenerator;
use Throwable;

final class BuildCommand extends Command
{
    use ConfiguresDocumentationOptions;

    protected $signature = 'docs:build
        {--source= : Markdown documentation source directory}
        {--output= : Static documentation output directory}
        {--base-url= : Base URL used by generated documentation links}
        {--config= : Optional Inkstone PHP config file}';

    protected $description = 'Build the static Inkstone documentation site.';

    public function handle(): int
    {
        $this->applyDocumentationOptions();

        try {
            $generator = app(StaticSiteGenerator::class);
            $pages = $generator->build();
        } catch (Throwable $throwable) {
            $this->components->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Inkstone built %d page%s into %s.', count($pages), count($pages) === 1 ? '' : 's', config('inkstone.output_path')));

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Commands;

use Illuminate\Console\Command;
use Inkstone\Commands\Concerns\ConfiguresDocumentationOptions;

final class ServeCommand extends Command
{
    use ConfiguresDocumentationOptions;

    protected $signature = 'docs:serve
        {--source= : Markdown documentation source directory}
        {--output= : Static documentation output directory}
        {--base-url= : Base URL used by generated documentation links}
        {--config= : Optional Inkstone PHP config file}
        {--host= : Host for the local preview server}
        {--port= : Port for the local preview server}
        {--watch : Rebuild before serving}';

    protected $description = 'Serve the generated Inkstone documentation locally.';

    public function handle(): int
    {
        $this->applyDocumentationOptions();

        if (is_string($this->option('host')) && $this->option('host') !== '') {
            config()->set('inkstone.server.host', $this->option('host'));
        }

        if (is_numeric($this->option('port'))) {
            config()->set('inkstone.server.port', (int) $this->option('port'));
        }

        if ($this->option('watch') || ! is_dir((string) config('inkstone.output_path'))) {
            $this->call('docs:build');
        }

        $host = (string) config('inkstone.server.host', '127.0.0.1');
        $port = (int) config('inkstone.server.port', 8080);
        $root = (string) config('inkstone.output_path');

        $this->components->info("Serving Inkstone docs at http://{$host}:{$port}");

        passthru(sprintf(
            '%s -S %s:%d -t %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($host),
            $port,
            escapeshellarg($root),
        ), $status);

        return (int) $status;
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Commands;

use Illuminate\Console\Command;
use Inkstone\Commands\Concerns\ConfiguresDocumentationOptions;
use Inkstone\Services\LocalDocumentationServer;

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
        {--watch : Watch docs, config, theme, and assets for changes while serving}';

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

        $watch = (bool) $this->option('watch');

        if ($watch) {
            config()->set('inkstone.build.clean_output_before_build', false);
        }

        if (($watch || ! is_dir((string) config('inkstone.output_path'))) && $this->call('docs:build') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $host = (string) config('inkstone.server.host', '127.0.0.1');
        $port = (int) config('inkstone.server.port', 8080);
        $root = (string) config('inkstone.output_path');
        $server = app(LocalDocumentationServer::class);

        $this->components->info("Serving Inkstone docs at http://{$host}:{$port}");

        if ($watch) {
            $this->components->info('Watching documentation sources for changes.');

            return $server->serveWithWatch($root, $host, $port, $this->watchPaths(), function (): int {
                $this->components->info('Documentation sources changed. Rebuilding documentation.');

                return $this->call('docs:build');
            });
        }

        return $server->serve($root, $host, $port);
    }

    /**
     * @return list<string>
     */
    private function watchPaths(): array
    {
        $paths = [
            (string) config('inkstone.source_path'),
            base_path('config/inkstone.php'),
            resource_path('views/vendor/inkstone'),
            resource_path('inkstone'),
        ];

        $configured = $this->option('config');

        if (is_string($configured) && $configured !== '') {
            $paths[] = $configured;
        }

        foreach ((array) config('inkstone.build.assets.additional_paths', []) as $path) {
            if (is_string($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique(array_filter($paths, static fn (string $path): bool => $path !== '')));
    }
}

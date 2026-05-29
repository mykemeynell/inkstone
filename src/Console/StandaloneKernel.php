<?php

declare(strict_types=1);

namespace Inkstone\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Support\Fluent;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\FileViewFinder;
use Inkstone\Commands\AiPromptCommand;
use Inkstone\Commands\BuildCommand;
use Inkstone\Commands\CleanCommand;
use Inkstone\Commands\InstallCommand;
use Inkstone\Commands\ServeCommand;
use Inkstone\Providers\InkstoneServiceProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StandaloneKernel
{
    public function __construct(private readonly string $basePath) {}

    public function handle(InputInterface $input, OutputInterface $output): int
    {
        $app = $this->bootstrapApplication();

        $console = new ConsoleApplication($app, $app->make('events'), 'Inkstone');
        $console->setName('Inkstone');
        $console->resolveCommands([
            InstallCommand::class,
            BuildCommand::class,
            ServeCommand::class,
            CleanCommand::class,
            AiPromptCommand::class,
        ]);

        return $console->run($input, $output);
    }

    private function bootstrapApplication(): StandaloneApplication
    {
        $app = new StandaloneApplication($this->basePath);

        Container::setInstance($app);
        $app->instance('app', $app);
        $app->instance(Container::class, $app);
        $app->alias('app', \Illuminate\Contracts\Container\Container::class);

        $files = new IlluminateFilesystem;
        $app->instance('files', $files);
        $app->instance(IlluminateFilesystem::class, $files);

        $events = new Dispatcher($app);
        $app->instance('events', $events);
        $app->instance(Dispatcher::class, $events);
        $app->alias('events', \Illuminate\Contracts\Events\Dispatcher::class);

        $app->instance('config', new Repository([
            'app' => [
                'view' => [
                    'paths' => [$app->resourcePath('views')],
                    'compiled' => $app->storagePath('framework/views'),
                ],
            ],
        ]));

        $this->registerViewServices($app, $files, $events);

        $provider = new InkstoneServiceProvider($app);
        $provider->register();
        $provider->boot();

        $this->loadStandaloneConfig($app);

        return $app;
    }

    private function registerViewServices(StandaloneApplication $app, IlluminateFilesystem $files, Dispatcher $events): void
    {
        $app->singleton('view.engine.resolver', function () use ($app, $files): EngineResolver {
            $resolver = new EngineResolver;

            $resolver->register('file', static fn (): FileEngine => new FileEngine($files));
            $resolver->register('php', static fn (): PhpEngine => new PhpEngine($files));
            $resolver->register('blade', function () use ($app, $files): CompilerEngine {
                $compiledPath = sys_get_temp_dir().'/inkstone/views/'.sha1($app->basePath());
                $files->ensureDirectoryExists($compiledPath);

                return new CompilerEngine(new BladeCompiler($files, $compiledPath));
            });

            return $resolver;
        });

        $app->singleton('view.finder', static function () use ($app, $files): FileViewFinder {
            return new FileViewFinder($files, [$app->resourcePath('views')]);
        });

        $app->singleton('view', static function () use ($app, $events): ViewFactory {
            $factory = new ViewFactory(
                $app->make('view.engine.resolver'),
                $app->make('view.finder'),
                $events,
            );

            $factory->setContainer($app);

            return $factory;
        });

        $app->alias('view', ViewFactoryContract::class);
    }

    private function loadStandaloneConfig(StandaloneApplication $app): void
    {
        $config = $app->make('config');
        $paths = [
            $app->basePath('inkstone.php'),
            $app->configPath('inkstone.php'),
        ];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $loaded = require $path;

            if (is_array($loaded)) {
                $config->set('inkstone', array_replace_recursive((array) $config->get('inkstone', []), $loaded));
            }
        }

        $config->set('inkstone.standalone', new Fluent([
            'enabled' => true,
            'base_path' => $app->basePath(),
        ]));
    }
}

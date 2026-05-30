<?php

declare(strict_types=1);

namespace Inkstone\Providers;

use Illuminate\Support\ServiceProvider;
use Inkstone\Commands\AiPromptCommand;
use Inkstone\Commands\BuildCommand;
use Inkstone\Commands\CleanCommand;
use Inkstone\Commands\InstallCommand;
use Inkstone\Commands\ServeCommand;
use Inkstone\Contracts\DocumentDiscoverer;
use Inkstone\Contracts\DocumentRenderer;
use Inkstone\Contracts\MarkdownParser;
use Inkstone\Contracts\NavigationBuilder as NavigationBuilderContract;
use Inkstone\Contracts\SearchIndexer;
use Inkstone\Contracts\StaticSiteGenerator;
use Inkstone\Contracts\Transformer;
use Inkstone\Demos\DemoRendererRegistry;
use Inkstone\Demos\DemoRuntimeResolver;
use Inkstone\Demos\SimpleDemoRuntime;
use Inkstone\Generators\StaticDocumentationGenerator;
use Inkstone\Parsers\CommonMarkMarkdownParser;
use Inkstone\Pipelines\TransformerPipeline;
use Inkstone\Renderers\BladeDocumentRenderer;
use Inkstone\Services\AssetManifest;
use Inkstone\Services\FilesystemDocumentDiscoverer;
use Inkstone\Services\FileSystemWriter;
use Inkstone\Services\LocalDocumentationServer;
use Inkstone\Services\NavigationBuilder;
use Inkstone\Services\SearchDriverConfig;
use Inkstone\Services\ThemeResolver;
use Inkstone\Transformers\DemoBlockTransformer;
use Inkstone\Transformers\GitHubRelativeLinkTransformer;
use Inkstone\Transformers\SyntaxHighlightTransformer;
use Symfony\Component\Filesystem\Filesystem;

class InkstoneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/inkstone.php', 'inkstone');

        $this->app->singleton(Filesystem::class);

        $this->app->singleton(DocumentDiscoverer::class, function (): DocumentDiscoverer {
            return new FilesystemDocumentDiscoverer(
                (string) config('inkstone.source_path'),
                (string) config('inkstone.site.base_url', ''),
                (bool) config('inkstone.build.pretty_urls', true),
                (array) config('inkstone.discovery.ignore', []),
            );
        });

        $this->app->singleton(MarkdownParser::class, function (): MarkdownParser {
            return new CommonMarkMarkdownParser((array) config('inkstone.markdown', []));
        });

        $this->app->singleton(NavigationBuilderContract::class, NavigationBuilder::class);
        $this->app->singleton(SearchDriverConfig::class);
        $this->app->singleton(SearchIndexer::class, function ($app): SearchIndexer {
            $config = $app->make(SearchDriverConfig::class);
            $indexer = $app->make($config->driverClass(), $config->parameters());

            if (! $indexer instanceof SearchIndexer) {
                throw new \RuntimeException('Configured search driver must implement '.SearchIndexer::class.'.');
            }

            return $indexer;
        });
        $this->app->singleton(ThemeResolver::class);
        $this->app->singleton(AssetManifest::class);
        $this->app->singleton(DocumentRenderer::class, BladeDocumentRenderer::class);
        $this->app->singleton(FileSystemWriter::class);
        $this->app->singleton(LocalDocumentationServer::class);
        $this->app->singleton(DemoRendererRegistry::class);
        $this->app->singleton(DemoRuntimeResolver::class, function (): DemoRuntimeResolver {
            return new DemoRuntimeResolver((array) config('inkstone.demos', []));
        });

        $this->app->singleton(SimpleDemoRuntime::class, function ($app): SimpleDemoRuntime {
            return new SimpleDemoRuntime(
                (array) config('inkstone.demos', []),
                $app->make(DemoRuntimeResolver::class),
            );
        });

        $this->app->singleton(TransformerPipeline::class, function ($app): TransformerPipeline {
            $transformers = [];

            foreach ((array) config('inkstone.transformers', []) as $key => $value) {
                $class = $this->transformerClass($key, $value);

                if ($class === null) {
                    continue;
                }

                $transformer = $app->make($class);

                if ($transformer instanceof Transformer) {
                    $transformers[] = $transformer;
                }
            }

            return new TransformerPipeline($transformers);
        });

        $this->app->bind(GitHubRelativeLinkTransformer::class, function (): GitHubRelativeLinkTransformer {
            return new GitHubRelativeLinkTransformer((array) config('inkstone.github', []));
        });

        $this->app->bind(SyntaxHighlightTransformer::class, function (): SyntaxHighlightTransformer {
            return new SyntaxHighlightTransformer((array) config('inkstone.theme.syntax_highlighting', []));
        });

        $this->app->bind(DemoBlockTransformer::class, function ($app): DemoBlockTransformer {
            return new DemoBlockTransformer(
                $app->make(SimpleDemoRuntime::class),
                $app->make(DemoRendererRegistry::class),
                (array) config('inkstone.demos', []),
            );
        });

        $this->app->singleton(StaticSiteGenerator::class, StaticDocumentationGenerator::class);
        $this->app->alias(StaticSiteGenerator::class, 'inkstone');
    }

    private function transformerClass(int|string $key, mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_string($key) && $value === true) {
            return $key;
        }

        if (is_array($value) && ($value['enabled'] ?? true) !== false && is_string($value['class'] ?? null)) {
            return $value['class'];
        }

        return null;
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'inkstone');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                BuildCommand::class,
                ServeCommand::class,
                CleanCommand::class,
                AiPromptCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../config/inkstone.php' => config_path('inkstone.php'),
            ], 'inkstone-config');

            $this->publishes([
                __DIR__.'/../../resources/views/themes/default' => resource_path('views/vendor/inkstone/themes/default'),
            ], 'inkstone-theme');

            $this->publishes([
                __DIR__.'/../../resources/css' => resource_path('inkstone/css'),
                __DIR__.'/../../resources/js' => resource_path('inkstone/js'),
            ], 'inkstone-assets');

            $this->publishes([
                __DIR__.'/../../stubs/deploy' => base_path('deploy/inkstone'),
            ], 'inkstone-deploy');

            $this->publishes([
                __DIR__.'/../../stubs/ai' => base_path('inkstone/ai'),
            ], 'inkstone-ai');
        }
    }
}

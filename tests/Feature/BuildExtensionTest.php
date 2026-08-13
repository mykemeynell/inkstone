<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Inkstone\Contracts\DocumentDiscoverer;
use Inkstone\Contracts\DocumentRenderer;
use Inkstone\Contracts\MarkdownParser;
use Inkstone\Contracts\NavigationBuilder;
use Inkstone\Contracts\SearchIndexer;
use Inkstone\Contracts\StaticSiteGenerator;
use Inkstone\DTOs\BuildContext;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\RenderedPage;
use Inkstone\Generators\StaticDocumentationGenerator;
use Inkstone\Pipelines\TransformerPipeline;
use Inkstone\Services\AssetManifest;
use Inkstone\Services\FileSystemWriter;
use Inkstone\Services\LinkChecker;
use Inkstone\Tests\Support\Extensions\ExtensionExecutionRecorder;
use Inkstone\Tests\Support\Extensions\FirstRecordingBuildExtension;
use Inkstone\Tests\Support\Extensions\SecondRecordingBuildExtension;
use Inkstone\Tests\Support\Extensions\StandaloneManifestExtension;
use Inkstone\Tests\Support\Extensions\ThrowingBuildExtension;
use Inkstone\Tests\TestCase;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class BuildExtensionTest extends TestCase
{
    private string $outputPath;

    private string $packagePath;

    private string $standaloneOutputPath;

    private string $standaloneConfigPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packagePath = dirname(__DIR__, 2);
        $this->outputPath = base_path('build/extension-feature');
        $this->standaloneOutputPath = $this->packagePath.'/build/standalone-extension-feature';
        $this->standaloneConfigPath = $this->packagePath.'/build/standalone-extension.php';

        config()->set('inkstone.source_path', __DIR__.'/../fixtures');
        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.site.base_url', '/docs');
        config()->set('inkstone.demos.enabled', false);
        config()->set('inkstone.build.asset_hashing', false);
        config()->set('inkstone.search.driver', 'json');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove([
            $this->outputPath,
            $this->standaloneOutputPath,
            $this->standaloneConfigPath,
        ]);

        parent::tearDown();
    }

    public function test_build_context_exposes_readonly_processed_build_data(): void
    {
        $document = new Document(
            sourcePath: 'docs/README.md',
            relativePath: 'README.md',
            slug: '',
            url: '/docs',
            html: '<h1 id="docs">Docs</h1>',
        );
        $page = new RenderedPage($document, '<!doctype html><title>Docs</title>', '/tmp/docs/index.html');
        $context = new BuildContext([$document], [$page], '/tmp/docs');

        $this->assertSame([$document], $context->documents);
        $this->assertSame([$page], $context->pages);
        $this->assertSame('/tmp/docs', $context->outputPath);

        $reflection = new ReflectionClass($context);

        $this->assertTrue($reflection->getProperty('documents')->isReadOnly());
        $this->assertTrue($reflection->getProperty('pages')->isReadOnly());
        $this->assertTrue($reflection->getProperty('outputPath')->isReadOnly());
    }

    public function test_configured_extensions_are_lazily_resolved_and_run_in_order_after_core_output(): void
    {
        $recorder = new ExtensionExecutionRecorder($this->outputPath);
        $this->app->instance(ExtensionExecutionRecorder::class, $recorder);
        config()->set('inkstone.extensions', [
            FirstRecordingBuildExtension::class,
            SecondRecordingBuildExtension::class,
            FirstRecordingBuildExtension::class,
        ]);

        $generator = $this->app->make(StaticSiteGenerator::class);

        $this->assertSame([], $recorder->constructed);

        $pages = $generator->build();

        $this->assertSame(['first', 'second'], $recorder->constructed);
        $this->assertSame(['first', 'second'], $recorder->executed);
        $this->assertSame([
            'first' => ['page' => true, 'search' => true, 'robots' => true, 'assets' => true],
            'second' => ['page' => true, 'search' => true, 'robots' => true, 'assets' => true],
        ], $recorder->artifactsAtConstruction);
        $this->assertCount(2, $recorder->contexts);

        foreach ($recorder->contexts as $context) {
            $this->assertSame($this->outputPath, $context->outputPath);
            $this->assertSame($pages, $context->pages);
            $this->assertNotEmpty($context->documents);
            $this->assertContainsOnlyInstancesOf(Document::class, $context->documents);
            $this->assertContainsOnlyInstancesOf(RenderedPage::class, $context->pages);
            $this->assertSame(count($context->documents), count($context->pages));
            $this->assertNotSame('', $context->documents[0]->html);
        }

        $this->assertFileExists($this->outputPath.'/extension-manifest.json');
        $manifest = json_decode(
            file_get_contents($this->outputPath.'/extension-manifest.json') ?: '',
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame(count($pages), $manifest['pages'] ?? null);
        $this->assertContains('/docs', $manifest['documents'] ?? []);
    }

    public function test_standalone_config_lazily_discovers_and_resolves_an_extension(): void
    {
        (new Filesystem)->mkdir(dirname($this->standaloneConfigPath));
        file_put_contents($this->standaloneConfigPath, sprintf(
            <<<'PHP'
<?php

declare(strict_types=1);

return [
    'extensions' => [
        %s::class,
    ],
    'demos' => [
        'enabled' => false,
    ],
];
PHP,
            StandaloneManifestExtension::class,
        ));

        $process = new Process([
            PHP_BINARY,
            $this->packagePath.'/bin/inkstone',
            'docs:build',
            '--config='.$this->standaloneConfigPath,
            '--source=stubs/docs',
            '--output=build/standalone-extension-feature',
            '--base-url=/docs',
        ], $this->packagePath);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
        $this->assertFileExists($this->standaloneOutputPath.'/standalone-extension.json');

        $manifest = json_decode(
            file_get_contents($this->standaloneOutputPath.'/standalone-extension.json') ?: '',
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('constructor-injected', $manifest['dependency'] ?? null);
        $this->assertSame($this->standaloneOutputPath, $manifest['output_path'] ?? null);
        $this->assertNotEmpty($manifest['documents'] ?? []);
        $this->assertSame(count($manifest['documents'] ?? []), $manifest['page_count'] ?? null);
        $this->assertSame(
            ['page' => true, 'search' => true, 'robots' => true, 'assets' => true],
            $manifest['core_artifacts'] ?? null,
        );
    }

    public function test_the_previous_generator_constructor_still_runs_the_default_sitemap_extension(): void
    {
        $assets = app(AssetManifest::class);
        $renderer = new class implements DocumentRenderer
        {
            public function render(Document $document, array $documents, array $navigation, string $outputPath): RenderedPage
            {
                return new RenderedPage($document, '<!doctype html><title>'.$document->title().'</title>', $outputPath);
            }
        };
        $dependencies = [
            app(DocumentDiscoverer::class),
            app(MarkdownParser::class),
            app(TransformerPipeline::class),
            app(NavigationBuilder::class),
            $renderer,
            app(SearchIndexer::class),
            app(FileSystemWriter::class),
            $assets,
            app(LinkChecker::class),
        ];
        $legacyConfig = (array) config('inkstone');
        unset($legacyConfig['extensions']);
        $legacyConfig['build']['generate_sitemap'] = true;

        $legacyContainer = new Container;
        $legacyContainer->instance('config', new Repository([
            'app' => ['url' => 'https://legacy.example.test'],
            'inkstone' => $legacyConfig,
        ]));
        $applicationContainer = Container::getInstance();

        try {
            Container::setInstance($legacyContainer);

            $generator = new StaticDocumentationGenerator(...$dependencies);
            $pages = $generator->build();
        } finally {
            Container::setInstance($applicationContainer);
        }

        $this->assertNotEmpty($pages);
        $this->assertFileExists($this->outputPath.'/sitemap.xml');
        $this->assertStringContainsString(
            '<loc>https://legacy.example.test/docs</loc>',
            file_get_contents($this->outputPath.'/sitemap.xml') ?: '',
        );
    }

    public function test_an_extension_failure_uses_the_build_commands_existing_failure_handling(): void
    {
        config()->set('inkstone.extensions', [ThrowingBuildExtension::class]);

        $this->artisan('docs:build')
            ->expectsOutputToContain('The application build extension failed.')
            ->assertExitCode(1);
    }
}

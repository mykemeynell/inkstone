<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class BuildCommandTest extends TestCase
{
    private string $outputPath;

    private string $brandSourcePath;

    private string $distPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/test-docs');
        $this->brandSourcePath = base_path('build/brand-source');
        $this->distPath = base_path('build/vite-dist');

        config()->set('inkstone.source_path', __DIR__.'/../fixtures');
        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.github.repository', 'https://github.com/vendor/package');
        config()->set('inkstone.site.base_url', '/docs');
        config()->set('inkstone.demos.enabled', false);
        config()->set('inkstone.build.asset_hashing', false);
        config()->set('inkstone.search.driver', 'json');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove([$this->outputPath, $this->brandSourcePath, $this->distPath]);

        parent::tearDown();
    }

    public function test_it_builds_static_docs_output(): void
    {
        $this->artisan('docs:build')->assertExitCode(0);

        $this->assertFileExists($this->outputPath.'/index.html');
        $this->assertFileExists($this->outputPath.'/search-index.json');
        $this->assertFileExists($this->outputPath.'/assets/css/inkstone.css');
        $this->assertStringContainsString('Inkstone Docs', file_get_contents($this->outputPath.'/index.html') ?: '');
        $this->assertStringContainsString('Built with Inkstone', file_get_contents($this->outputPath.'/index.html') ?: '');
    }

    public function test_default_build_asset_urls_match_the_output_root(): void
    {
        config()->set('inkstone.site.base_url', '');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringContainsString('href="/assets/css/inkstone.css"', $html);
        $this->assertStringContainsString('href="/assets/css/themes/default.css"', $html);
        $this->assertStringContainsString('src="/assets/js/inkstone.js"', $html);
        $this->assertStringContainsString('data-inkstone-search-open', $html);
        $this->assertStringContainsString('data-inkstone-search-overlay', $html);
        $this->assertStringContainsString('data-inkstone-search-index="/search-index.json"', $html);
        $this->assertStringNotContainsString('/docs/assets/', $html);
    }

    public function test_it_uses_vite_manifest_assets_when_available(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir([
            $this->distPath.'/.vite',
            $this->distPath.'/assets',
        ]);

        file_put_contents($this->distPath.'/assets/inkstone-a1b2c3.css', 'body{}');
        file_put_contents($this->distPath.'/assets/default-d4e5f6.css', ':root{}');
        file_put_contents($this->distPath.'/assets/inkstone-g7h8i9.js', 'console.log("inkstone");');
        file_put_contents($this->distPath.'/.vite/manifest.json', json_encode([
            'resources/css/inkstone.css' => ['file' => 'assets/inkstone-a1b2c3.css'],
            'resources/css/themes/default.css' => ['file' => 'assets/default-d4e5f6.css'],
            'resources/js/inkstone.js' => ['file' => 'assets/inkstone-g7h8i9.js'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        config()->set('inkstone.build.assets.dist_path', $this->distPath);
        config()->set('inkstone.build.assets.manifest_path', $this->distPath.'/.vite/manifest.json');
        config()->set('inkstone.build.asset_hashing', true);

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertFileExists($this->outputPath.'/assets/inkstone-a1b2c3.css');
        $this->assertFileExists($this->outputPath.'/assets/default-d4e5f6.css');
        $this->assertFileExists($this->outputPath.'/assets/inkstone-g7h8i9.js');
        $this->assertFileDoesNotExist($this->outputPath.'/.vite/manifest.json');
        $this->assertStringContainsString('href="/docs/assets/inkstone-a1b2c3.css"', $html);
        $this->assertStringContainsString('href="/docs/assets/default-d4e5f6.css"', $html);
        $this->assertStringContainsString('src="/docs/assets/inkstone-g7h8i9.js"', $html);
        $this->assertStringNotContainsString('assets/css/inkstone.css', $html);
    }

    public function test_stale_manifest_falls_back_to_source_assets(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir([
            $this->distPath.'/.vite',
            $this->distPath.'/assets',
        ]);

        file_put_contents($this->distPath.'/assets/inkstone-a1b2c3.css', 'body{}');
        file_put_contents($this->distPath.'/.vite/manifest.json', json_encode([
            'resources/css/inkstone.css' => ['file' => 'assets/inkstone-a1b2c3.css'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Force the manifest mtime to be older than the source assets by touching it
        // with a timestamp in the past, simulating a stale dist directory
        touch($this->distPath.'/.vite/manifest.json', strtotime('2000-01-01 00:00:00'));

        config()->set('inkstone.build.assets.dist_path', $this->distPath);
        config()->set('inkstone.build.assets.manifest_path', $this->distPath.'/.vite/manifest.json');
        config()->set('inkstone.build.asset_hashing', true);

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringNotContainsString('assets/inkstone-a1b2c3.css', $html);
        $this->assertStringContainsString('assets/css/inkstone.css', $html);
    }

    public function test_it_does_not_render_search_or_write_index_when_search_is_disabled(): void
    {
        config()->set('inkstone.search.enabled', false);

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertFileDoesNotExist($this->outputPath.'/search-index.json');
        $this->assertStringNotContainsString('data-inkstone-search', $html);
        $this->assertStringNotContainsString('data-inkstone-search-results', $html);
    }

    public function test_it_uses_the_configured_search_driver_index_path(): void
    {
        config()->set('inkstone.search.drivers.json.config.index_path', 'search/docs-index.json');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertFileExists($this->outputPath.'/search/docs-index.json');
        $this->assertFileDoesNotExist($this->outputPath.'/search-index.json');
        $this->assertStringContainsString('data-inkstone-search-index="/docs/search/docs-index.json"', $html);
    }

    public function test_it_renders_external_scripts_and_config_for_selected_driver(): void
    {
        config()->set('inkstone.search.driver', 'lunr');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringContainsString('https://unpkg.com/lunr/lunr.js', $html);
        $this->assertStringContainsString('data-inkstone-search-driver="lunr"', $html);
        $this->assertStringContainsString('data-inkstone-search-config="{&quot;index_path&quot;:&quot;lunr-index.json&quot;', $html);
    }

    public function test_available_css_themes_use_the_default_layout_when_no_theme_view_exists(): void
    {
        config()->set('inkstone.theme.name', 'ember');
        config()->set('inkstone.theme.layout', 'default');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringContainsString('href="/docs/assets/css/themes/ember.css"', $html);
        $this->assertStringContainsString('Inkstone Docs', $html);
    }

    public function test_missing_theme_layout_falls_back_to_the_default_layout(): void
    {
        config()->set('inkstone.theme.name', 'forest');
        config()->set('inkstone.theme.layout', 'missing-layout');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringContainsString('href="/docs/assets/css/themes/forest.css"', $html);
        $this->assertStringContainsString('Inkstone Docs', $html);
    }

    public function test_it_discovers_source_logo_and_favicon_assets(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->brandSourcePath);
        file_put_contents($this->brandSourcePath.'/README.md', '# Branded Docs');
        file_put_contents($this->brandSourcePath.'/favicon.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($this->brandSourcePath.'/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($this->brandSourcePath.'/logo-dark.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        config()->set('inkstone.source_path', $this->brandSourcePath);
        config()->set('inkstone.site.favicon', null);
        config()->set('inkstone.site.logo', null);

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertFileExists($this->outputPath.'/favicon.svg');
        $this->assertFileExists($this->outputPath.'/assets/logo.svg');
        $this->assertFileExists($this->outputPath.'/assets/logo-dark.svg');
        $this->assertStringContainsString('/docs/favicon.svg', $html);
        $this->assertStringContainsString('/docs/assets/logo.svg', $html);
        $this->assertStringContainsString('/docs/assets/logo-dark.svg', $html);
        $this->assertStringContainsString('data-brand-logo="light"', $html);
        $this->assertStringContainsString('data-brand-logo="dark"', $html);
    }

    public function test_dark_logo_falls_back_to_light_logo_when_not_found(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->brandSourcePath);
        file_put_contents($this->brandSourcePath.'/README.md', '# Branded Docs');
        file_put_contents($this->brandSourcePath.'/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        config()->set('inkstone.source_path', $this->brandSourcePath);
        config()->set('inkstone.site.logo', null);

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertFileExists($this->outputPath.'/assets/logo.svg');
        $this->assertFileDoesNotExist($this->outputPath.'/assets/logo-dark.svg');
        $this->assertStringContainsString('/docs/assets/logo.svg', $html);
        // Both light and dark images use the same src when no dark logo exists
        $this->assertStringContainsString('data-brand-logo="light"', $html);
        $this->assertStringContainsString('data-brand-logo="dark"', $html);
    }
}

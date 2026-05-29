<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class BuildCommandTest extends TestCase
{
    private string $outputPath;

    private string $brandSourcePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/test-docs');
        $this->brandSourcePath = base_path('build/brand-source');

        config()->set('inkstone.docs_path', __DIR__.'/../fixtures');
        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.github.repository', 'https://github.com/vendor/package');
        config()->set('inkstone.site.base_url', '/docs');
        config()->set('inkstone.demos.enabled', false);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove([$this->outputPath, $this->brandSourcePath]);

        parent::tearDown();
    }

    public function test_it_builds_static_docs_output(): void
    {
        $this->artisan('docs:build')->assertExitCode(0);

        $this->assertFileExists($this->outputPath.'/index.html');
        $this->assertFileExists($this->outputPath.'/search-index.json');
        $this->assertFileExists($this->outputPath.'/assets/css/inkstone.css');
        $this->assertStringContainsString('Inkstone Documentation', file_get_contents($this->outputPath.'/index.html') ?: '');
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
        $this->assertStringContainsString('data-inkstone-search-index="/search-index.json"', $html);
        $this->assertStringNotContainsString('/docs/assets/', $html);
    }

    public function test_available_css_themes_use_the_default_layout_when_no_theme_view_exists(): void
    {
        config()->set('inkstone.theme.name', 'ember');
        config()->set('inkstone.theme.layout', 'default');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringContainsString('href="/docs/assets/css/themes/ember.css"', $html);
        $this->assertStringContainsString('Inkstone Documentation', $html);
    }

    public function test_missing_theme_layout_falls_back_to_the_default_layout(): void
    {
        config()->set('inkstone.theme.name', 'forest');
        config()->set('inkstone.theme.layout', 'missing-layout');

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertStringContainsString('href="/docs/assets/css/themes/forest.css"', $html);
        $this->assertStringContainsString('Inkstone Documentation', $html);
    }

    public function test_it_discovers_source_logo_and_favicon_assets(): void
    {
        $filesystem = new Filesystem;
        $filesystem->mkdir($this->brandSourcePath);
        file_put_contents($this->brandSourcePath.'/README.md', '# Branded Docs');
        file_put_contents($this->brandSourcePath.'/favicon.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($this->brandSourcePath.'/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        config()->set('inkstone.docs_path', $this->brandSourcePath);
        config()->set('inkstone.site.favicon', null);
        config()->set('inkstone.site.logo', null);

        $this->artisan('docs:build')->assertExitCode(0);

        $html = file_get_contents($this->outputPath.'/index.html') ?: '';

        $this->assertFileExists($this->outputPath.'/favicon.svg');
        $this->assertFileExists($this->outputPath.'/assets/logo.svg');
        $this->assertStringContainsString('/docs/favicon.svg', $html);
        $this->assertStringContainsString('/docs/assets/logo.svg', $html);
    }
}

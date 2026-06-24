<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class RouteServingTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/route-serving-test');

        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.routes.path', 'docs');
        config()->set('inkstone.routes.domain', null);
        config()->set('inkstone.routes.middleware', []);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_it_serves_generated_docs_from_the_configured_path(): void
    {
        $this->writeOutputFile('index.html', '<h1>Generated Docs</h1>');

        \Inkstone::routes();

        $this->get('/docs')
            ->assertOk()
            ->assertStreamedContent('<h1>Generated Docs</h1>');
    }

    public function test_it_serves_nested_pretty_url_pages(): void
    {
        $this->writeOutputFile('getting-started/installation/index.html', '<h1>Installation</h1>');

        \Inkstone::routes();

        $this->get('/docs/getting-started/installation')
            ->assertOk()
            ->assertStreamedContent('<h1>Installation</h1>');
    }

    public function test_it_serves_direct_generated_files(): void
    {
        $this->writeOutputFile('assets/css/inkstone.css', 'body{}');
        $this->writeOutputFile('search-index.json', '{"entries":[]}');
        $this->writeOutputFile('sitemap.xml', '<urlset></urlset>');
        $this->writeOutputFile('robots.txt', "User-agent: *\nAllow: /\n");

        \Inkstone::routes();

        $this->get('/docs/assets/css/inkstone.css')
            ->assertOk()
            ->assertStreamedContent('body{}');

        $this->get('/docs/search-index.json')
            ->assertOk()
            ->assertStreamedContent('{"entries":[]}');

        $this->get('/docs/sitemap.xml')
            ->assertOk()
            ->assertStreamedContent('<urlset></urlset>');

        $this->get('/docs/robots.txt')
            ->assertOk()
            ->assertStreamedContent("User-agent: *\nAllow: /\n");
    }

    public function test_it_applies_the_configured_route_domain(): void
    {
        config()->set('inkstone.routes.domain', 'docs.example.test');

        $this->writeOutputFile('index.html', '<h1>Domain Docs</h1>');

        \Inkstone::routes();

        $this->get('http://docs.example.test/docs')
            ->assertOk()
            ->assertStreamedContent('<h1>Domain Docs</h1>');

        $this->get('http://example.test/docs')->assertNotFound();
    }

    public function test_it_returns_not_found_for_missing_output_files(): void
    {
        $this->writeOutputFile('index.html', '<h1>Generated Docs</h1>');

        \Inkstone::routes();

        $this->get('/docs/missing-page')->assertNotFound();
    }

    public function test_it_rejects_path_traversal_attempts(): void
    {
        $this->writeOutputFile('index.html', '<h1>Generated Docs</h1>');

        \Inkstone::routes();

        $this->get('/docs/%2E%2E/composer.json')->assertNotFound();
    }

    public function test_it_can_be_mounted_at_the_application_root(): void
    {
        config()->set('inkstone.routes.path', '');

        $this->writeOutputFile('index.html', '<h1>Root Docs</h1>');

        \Inkstone::routes();

        $this->get('/')
            ->assertOk()
            ->assertStreamedContent('<h1>Root Docs</h1>');
    }

    private function writeOutputFile(string $path, string $contents): void
    {
        $target = $this->outputPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

        (new Filesystem)->mkdir(dirname($target));

        file_put_contents($target, $contents);
    }
}

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
            ->assertSee('<h1>Generated Docs</h1>', false);
    }

    public function test_it_rewrites_root_relative_generated_urls_to_the_route_path(): void
    {
        config()->set('inkstone.site.base_url', '');

        $this->writeOutputFile('index.html', <<<'HTML'
<!doctype html>
<html>
<head>
    <link rel="stylesheet" href="/assets/css/inkstone.css">
    <script src="/assets/js/inkstone.js"></script>
</head>
<body>
    <a href="/">Home</a>
    <a href="/getting-started">Getting Started</a>
    <a href="/login">Application Login</a>
    <input data-inkstone-search-index="/search-index.json">
</body>
</html>
HTML);
        $this->writeOutputFile('assets/css/inkstone.css', 'body{}');
        $this->writeOutputFile('assets/js/inkstone.js', 'console.log("inkstone");');
        $this->writeOutputFile('getting-started/index.html', '<h1>Getting Started</h1>');
        $this->writeOutputFile('search-index.json', '{"entries":[]}');

        \Inkstone::routes();

        $response = $this->get('/docs')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('href="/docs/assets/css/inkstone.css"', $html);
        $this->assertStringContainsString('src="/docs/assets/js/inkstone.js"', $html);
        $this->assertStringContainsString('href="/docs"', $html);
        $this->assertStringContainsString('href="/docs/getting-started"', $html);
        $this->assertStringContainsString('data-inkstone-search-index="/docs/search-index.json"', $html);
        $this->assertStringContainsString('href="/login"', $html);
    }

    public function test_it_serves_nested_pretty_url_pages(): void
    {
        $this->writeOutputFile('getting-started/installation/index.html', '<h1>Installation</h1>');

        \Inkstone::routes();

        $this->get('/docs/getting-started/installation')
            ->assertOk()
            ->assertSee('<h1>Installation</h1>', false);
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
            ->assertSee('<h1>Domain Docs</h1>', false);

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
            ->assertSee('<h1>Root Docs</h1>', false);
    }

    private function writeOutputFile(string $path, string $contents): void
    {
        $target = $this->outputPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

        (new Filesystem)->mkdir(dirname($target));

        file_put_contents($target, $contents);
    }
}

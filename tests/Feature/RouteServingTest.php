<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use DOMDocument;
use DOMElement;
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
        (new Filesystem)->remove([
            $this->outputPath,
            base_path('build/route-serving-source'),
        ]);

        parent::tearDown();
    }

    public function test_it_serves_generated_docs_from_the_configured_path(): void
    {
        $this->writeOutputFile('index.html', '<h1>Generated Docs</h1>');

        \Inkstone::routes();

        $response = $this->get('/docs')
            ->assertOk();

        $this->assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));

        $response
            ->assertSee('<h1>Generated Docs</h1>', false);
    }

    public function test_route_served_built_docs_rewrite_and_serve_browser_assets_from_the_mount_path(): void
    {
        $sourcePath = base_path('build/route-serving-source');
        (new Filesystem)->mkdir($sourcePath);
        file_put_contents($sourcePath.'/README.md', '# Branded Docs');
        file_put_contents($sourcePath.'/favicon.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($sourcePath.'/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($sourcePath.'/logo-dark.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        config()->set('inkstone.source_path', $sourcePath);
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.site.favicon', null);
        config()->set('inkstone.site.logo', null);
        config()->set('inkstone.demos.enabled', false);
        config()->set('inkstone.build.asset_hashing', false);
        config()->set('inkstone.search.driver', 'json');

        $this->artisan('docs:build')->assertExitCode(0);

        \Inkstone::routes();

        $response = $this->get('/docs')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('href="/docs/assets/css/inkstone.css"', $html);
        $this->assertStringContainsString('href="/docs/assets/css/themes/default.css"', $html);
        $this->assertStringContainsString('src="/docs/assets/js/inkstone.js"', $html);
        $this->assertStringContainsString('src="/docs/assets/js/search-driver.js"', $html);
        $this->assertStringContainsString('data-inkstone-search-index="/docs/search-index.json"', $html);
        $this->assertStringContainsString('href="/docs/favicon.svg"', $html);
        $this->assertStringContainsString('src="/docs/assets/logo.svg"', $html);

        foreach ($this->browserAssetUrls($html) as $url => $contentType) {
            $this->assertStringStartsWith('/docs/', $url, sprintf('Expected %s to be mounted below /docs.', $url));
            $this->assertResponseContentType($url, $contentType);
        }

        (new Filesystem)->remove($sourcePath);
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
    <a href="/getting-started?ref=home#install">Getting Started</a>
    <a href="/login">Application Login</a>
    <a href="//cdn.example.com/docs.css">CDN</a>
    <a href="#intro">Intro</a>
    <a href="https://example.com/docs">External</a>
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
        $this->assertStringContainsString('href="/docs/getting-started?ref=home#install"', $html);
        $this->assertStringContainsString('data-inkstone-search-index="/docs/search-index.json"', $html);
        $this->assertStringContainsString('href="/login"', $html);
        $this->assertStringContainsString('href="//cdn.example.com/docs.css"', $html);
        $this->assertStringContainsString('href="#intro"', $html);
        $this->assertStringContainsString('href="https://example.com/docs"', $html);
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
        $this->writeOutputFile('assets/js/inkstone.js', 'console.log("inkstone");');
        $this->writeOutputFile('search-index.json', '{"entries":[]}');
        $this->writeOutputFile('sitemap.xml', '<urlset></urlset>');
        $this->writeOutputFile('robots.txt', "User-agent: *\nAllow: /\n");

        \Inkstone::routes();

        $css = $this->get('/docs/assets/css/inkstone.css')
            ->assertOk();

        $this->assertStringStartsWith('text/css', (string) $css->headers->get('Content-Type'));
        $css->assertStreamedContent('body{}');

        $js = $this->get('/docs/assets/js/inkstone.js')
            ->assertOk();

        $this->assertStringStartsWith('application/javascript', (string) $js->headers->get('Content-Type'));
        $js->assertStreamedContent('console.log("inkstone");');

        $json = $this->get('/docs/search-index.json')
            ->assertOk();

        $this->assertStringStartsWith('application/json', (string) $json->headers->get('Content-Type'));
        $json->assertStreamedContent('{"entries":[]}');

        $xml = $this->get('/docs/sitemap.xml')
            ->assertOk();

        $this->assertStringStartsWith('application/xml', (string) $xml->headers->get('Content-Type'));
        $xml->assertStreamedContent('<urlset></urlset>');

        $text = $this->get('/docs/robots.txt')
            ->assertOk();

        $this->assertStringStartsWith('text/plain', (string) $text->headers->get('Content-Type'));
        $text->assertStreamedContent("User-agent: *\nAllow: /\n");
    }

    public function test_it_serves_browser_asset_types_with_safe_content_types(): void
    {
        $this->writeOutputFile('assets/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->writeOutputFile('assets/screenshot.png', 'png fixture');
        $this->writeOutputFile('assets/photo.jpg', 'jpg fixture');
        $this->writeOutputFile('assets/preview.webp', 'webp fixture');
        $this->writeOutputFile('assets/hero.avif', 'avif fixture');
        $this->writeOutputFile('assets/fonts/inter.woff2', 'font fixture');
        $this->writeOutputFile('assets/fonts/inter.woff', 'font fixture');
        $this->writeOutputFile('assets/js/inkstone.js.map', '{"version":3}');

        \Inkstone::routes();

        $this->assertResponseContentType('/docs/assets/logo.svg', 'image/svg+xml');
        $this->assertResponseContentType('/docs/assets/screenshot.png', 'image/png');
        $this->assertResponseContentType('/docs/assets/photo.jpg', 'image/jpeg');
        $this->assertResponseContentType('/docs/assets/preview.webp', 'image/webp');
        $this->assertResponseContentType('/docs/assets/hero.avif', 'image/avif');
        $this->assertResponseContentType('/docs/assets/fonts/inter.woff2', 'font/woff2');
        $this->assertResponseContentType('/docs/assets/fonts/inter.woff', 'font/woff');
        $this->assertResponseContentType('/docs/assets/js/inkstone.js.map', 'application/json');
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
        $this->get('/docs/%2e%2e%2fcomposer.json')->assertNotFound();
        $this->get('/docs/%252E%252E/composer.json')->assertNotFound();
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

    /**
     * @return array<string, string>
     */
    private function browserAssetUrls(string $html): array
    {
        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $urls = [];

        foreach ($document->getElementsByTagName('link') as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $rel = $link->getAttribute('rel');
            $href = $link->getAttribute('href');

            if ($href === '') {
                continue;
            }

            if ($rel === 'stylesheet') {
                $urls[$href] = 'text/css';
            } elseif ($rel === 'icon') {
                $urls[$href] = 'image/svg+xml';
            }
        }

        foreach ($document->getElementsByTagName('script') as $script) {
            if ($script instanceof DOMElement && $script->getAttribute('src') !== '') {
                $urls[$script->getAttribute('src')] = 'application/javascript';
            }
        }

        foreach ($document->getElementsByTagName('img') as $image) {
            if ($image instanceof DOMElement && $image->getAttribute('src') !== '') {
                $urls[$image->getAttribute('src')] = 'image/svg+xml';
            }
        }

        foreach ($document->getElementsByTagName('input') as $input) {
            if ($input instanceof DOMElement && $input->getAttribute('data-inkstone-search-index') !== '') {
                $urls[$input->getAttribute('data-inkstone-search-index')] = 'application/json';
            }
        }

        return $urls;
    }

    private function assertResponseContentType(string $uri, string $contentType): void
    {
        $response = $this->get($uri)->assertOk();

        $this->assertStringStartsWith(
            $contentType,
            (string) $response->headers->get('Content-Type'),
            sprintf('Expected %s to be served as %s.', $uri, $contentType),
        );
    }
}

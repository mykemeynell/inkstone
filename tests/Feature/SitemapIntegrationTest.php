<?php

declare(strict_types=1);

namespace Inkstone\Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Testing\TestResponse;
use Inkstone\Extensions\SitemapExtension;
use Inkstone\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class SitemapIntegrationTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/sitemap-integration-test');

        config()->set('app.url', 'https://application.example.test');
        config()->set('inkstone.source_path', __DIR__.'/../fixtures');
        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.routes.path', 'knowledge');
        config()->set('inkstone.routes.domain', 'docs.example.test');
        config()->set('inkstone.routes.middleware', []);
        config()->set('inkstone.demos.enabled', false);
        config()->set('inkstone.build.asset_hashing', false);
        config()->set('inkstone.search.driver', 'json');
        config()->set('inkstone.api.spec_path', __DIR__.'/../fixtures/docs');
        $this->app['url']->forceRootUrl('https://application.example.test');
        $this->app['url']->forceScheme('https');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_a_default_build_exposes_a_valid_sitemap_through_the_configured_laravel_route(): void
    {
        $pages = \Inkstone::build();

        $this->assertNotEmpty($pages);
        $this->assertFileExists($this->outputPath.'/sitemap.xml');

        \Inkstone::routes();

        $sitemapUrl = app(SitemapExtension::class)->url();

        $this->assertSame('https://docs.example.test/knowledge/sitemap.xml', $sitemapUrl);

        $response = $this->get($sitemapUrl)->assertOk();

        $this->assertStringStartsWith(
            'application/xml',
            (string) $response->headers->get('Content-Type'),
        );

        $document = new DOMDocument;

        $this->assertTrue($document->loadXML($this->responseBody($response)));

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $locations = $xpath->evaluate('/sitemap:urlset/sitemap:url/sitemap:loc');

        $this->assertNotFalse($locations);

        $urls = [];

        foreach ($locations as $location) {
            $urls[] = $location->textContent;
        }

        $this->assertContains('https://docs.example.test/knowledge', $urls);
        $this->assertContains(
            'https://docs.example.test/knowledge/docs/getting-started/installation',
            $urls,
        );
        $this->assertContains('https://docs.example.test/knowledge/api/openapi', $urls);
    }

    public function test_a_real_non_pretty_build_uses_html_urls_for_markdown_and_openapi_documents(): void
    {
        config()->set('inkstone.site.base_url', 'https://docs.example.test/knowledge');
        config()->set('inkstone.build.pretty_urls', false);

        \Inkstone::build();

        $contents = file_get_contents($this->outputPath.'/sitemap.xml');
        $this->assertIsString($contents);

        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($contents));

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $locations = $xpath->evaluate('/sitemap:urlset/sitemap:url/sitemap:loc');
        $this->assertNotFalse($locations);

        $urls = [];

        foreach ($locations as $location) {
            $urls[] = $location->textContent;
        }

        $this->assertContains(
            'https://docs.example.test/knowledge/docs/getting-started/installation.html',
            $urls,
        );
        $this->assertContains('https://docs.example.test/knowledge/api/openapi.html', $urls);
    }

    private function responseBody(TestResponse $response): string
    {
        $content = $response->baseResponse->getContent();

        if (is_string($content)) {
            return $content;
        }

        ob_start();
        $response->baseResponse->sendContent();

        return ob_get_clean() ?: '';
    }
}

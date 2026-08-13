<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Inkstone\DTOs\BuildContext;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\RenderedPage;
use Inkstone\Extensions\SitemapExtension;
use Inkstone\Services\CanonicalUrlResolver;
use Inkstone\Services\FileSystemWriter;
use Inkstone\Tests\TestCase;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class SitemapExtensionTest extends TestCase
{
    private const SITEMAP_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/sitemap-extension-test');

        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.build.generate_sitemap', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_it_writes_namespace_correct_xml_from_processed_document_urls(): void
    {
        config()->set('inkstone.site.base_url', 'https://docs.example.test/manual');

        $documents = [
            $this->document('README.md', '', 'https://docs.example.test/manual'),
            $this->document('guides/getting-started.md', 'guides/getting-started', 'https://docs.example.test/manual/guides/getting-started'),
            $this->document('api/openapi.yaml', 'api/openapi', 'https://docs.example.test/manual/api/openapi'),
            $this->document('reference.md', 'reference', 'https://docs.example.test/manual/reference.html'),
            $this->document('search.md', 'search', 'https://docs.example.test/manual/search?language=php&version=8.5'),
            $this->document('duplicate.md', 'duplicate', 'https://docs.example.test/manual/guides/getting-started'),
        ];

        app(SitemapExtension::class)->afterBuild($this->context($documents));

        $xml = $this->sitemapContents();
        $document = $this->loadXml($xml);
        $locations = $this->locations($document);

        $this->assertSame('UTF-8', strtoupper((string) $document->encoding));
        $this->assertInstanceOf(DOMElement::class, $document->documentElement);
        $this->assertSame('urlset', $document->documentElement->localName);
        $this->assertSame(self::SITEMAP_NAMESPACE, $document->documentElement->namespaceURI);
        $this->assertSame([
            'https://docs.example.test/manual',
            'https://docs.example.test/manual/guides/getting-started',
            'https://docs.example.test/manual/api/openapi',
            'https://docs.example.test/manual/reference.html',
            'https://docs.example.test/manual/search?language=php&version=8.5',
        ], $locations);
        $this->assertStringContainsString('language=php&amp;version=8.5', $xml);
    }

    public function test_it_resolves_a_relative_site_base_against_the_laravel_application_url(): void
    {
        config()->set('app.url', 'https://application.example.test');
        config()->set('inkstone.site.base_url', '/manual/v2');
        $this->app['url']->forceRootUrl('https://application.example.test');
        $this->app['url']->forceScheme('https');

        $documents = [
            $this->document('README.md', '', '/manual/v2'),
            $this->document('installation.md', 'installation', '/manual/v2/installation'),
        ];

        $extension = app(SitemapExtension::class);
        $extension->afterBuild($this->context($documents));

        $this->assertSame([
            'https://application.example.test/manual/v2',
            'https://application.example.test/manual/v2/installation',
        ], $this->locations($this->loadXml($this->sitemapContents())));
        $this->assertSame(
            'https://application.example.test/manual/v2/sitemap.xml',
            $extension->url(),
        );
    }

    public function test_it_uses_the_named_laravel_route_path_and_fixed_domain_when_no_site_base_is_configured(): void
    {
        config()->set('app.url', 'https://application.example.test');
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.routes.path', 'knowledge');
        config()->set('inkstone.routes.domain', 'docs.example.test');
        config()->set('inkstone.routes.middleware', []);
        $this->app['url']->forceRootUrl('https://application.example.test');
        $this->app['url']->forceScheme('https');

        \Inkstone::routes();

        $documents = [
            $this->document('README.md', '', '/'),
            $this->document('installation.md', 'installation', '/installation'),
        ];

        $extension = app(SitemapExtension::class);
        $extension->afterBuild($this->context($documents));

        $this->assertSame([
            'https://docs.example.test/knowledge',
            'https://docs.example.test/knowledge/installation',
        ], $this->locations($this->loadXml($this->sitemapContents())));
        $this->assertSame(
            'https://docs.example.test/knowledge/sitemap.xml',
            $extension->url(),
        );
    }

    public function test_url_honours_an_absolute_site_base_url(): void
    {
        config()->set('inkstone.site.base_url', 'https://cdn.example.test/package-docs/v3/');

        $this->assertSame(
            'https://cdn.example.test/package-docs/v3/sitemap.xml',
            app(SitemapExtension::class)->url(),
        );
    }

    public function test_it_uses_the_sitemap_protocol_limits_by_default(): void
    {
        $constructor = new ReflectionMethod(SitemapExtension::class, '__construct');
        $parameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        $this->assertSame(50_000, $parameters['maxUrls']->getDefaultValue());
        $this->assertSame(50 * 1024 * 1024, $parameters['maxBytes']->getDefaultValue());
    }

    public function test_it_rejects_a_sitemap_above_the_url_count_limit(): void
    {
        config()->set('inkstone.site.base_url', 'https://docs.example.test');

        $extension = new SitemapExtension(
            app(FileSystemWriter::class),
            app(CanonicalUrlResolver::class),
            maxUrls: 2,
        );
        $context = $this->context([
            $this->document('one.md', 'one', 'https://docs.example.test/one'),
            $this->document('two.md', 'two', 'https://docs.example.test/two'),
            $this->document('three.md', 'three', 'https://docs.example.test/three'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('2 URLs');

        $extension->afterBuild($context);
    }

    public function test_it_rejects_a_sitemap_above_the_byte_limit(): void
    {
        config()->set('inkstone.site.base_url', 'https://docs.example.test');

        $extension = new SitemapExtension(
            app(FileSystemWriter::class),
            app(CanonicalUrlResolver::class),
            maxBytes: 120,
        );
        $context = $this->context([
            $this->document('installation.md', 'installation', 'https://docs.example.test/installation'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('120 bytes');

        $extension->afterBuild($context);
    }

    /**
     * @param  list<Document>  $documents
     */
    private function context(array $documents): BuildContext
    {
        $pages = array_map(
            fn (Document $document): RenderedPage => new RenderedPage(
                document: $document,
                html: '<!doctype html><title>'.$document->title().'</title>',
                outputPath: $this->outputPath.'/'.($document->slug !== '' ? $document->slug.'/index.html' : 'index.html'),
            ),
            $documents,
        );

        return new BuildContext(
            documents: $documents,
            pages: $pages,
            outputPath: $this->outputPath,
        );
    }

    private function document(string $relativePath, string $slug, string $url): Document
    {
        return new Document(
            sourcePath: '/tmp/inkstone-sitemap-test/'.$relativePath,
            relativePath: $relativePath,
            slug: $slug,
            url: $url,
        );
    }

    private function sitemapContents(): string
    {
        $contents = file_get_contents($this->outputPath.'/sitemap.xml');

        $this->assertIsString($contents);

        return $contents;
    }

    private function loadXml(string $xml): DOMDocument
    {
        $document = new DOMDocument;

        $this->assertTrue($document->loadXML($xml));

        return $document;
    }

    /**
     * @return list<string>
     */
    private function locations(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('sitemap', self::SITEMAP_NAMESPACE);
        $nodes = $xpath->query('/sitemap:urlset/sitemap:url/sitemap:loc');

        $this->assertNotFalse($nodes);

        $locations = [];

        foreach ($nodes as $node) {
            $locations[] = $node->textContent;
        }

        return $locations;
    }
}

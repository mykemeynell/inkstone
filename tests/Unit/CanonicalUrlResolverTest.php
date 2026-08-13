<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\BuildContext;
use Inkstone\DTOs\Document;
use Inkstone\Extensions\SitemapExtension;
use Inkstone\Services\CanonicalUrlResolver;
use Inkstone\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class CanonicalUrlResolverTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = base_path('build/canonical-url-resolver-test');

        config()->set('inkstone.output_path', $this->outputPath);
        config()->set('inkstone.build.generate_sitemap', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->outputPath);

        parent::tearDown();
    }

    public function test_the_configured_application_url_is_the_document_origin_for_a_relative_site_base(): void
    {
        config()->set('app.url', 'https://trusted.example.test');
        config()->set('inkstone.site.base_url', '/manual');
        $this->app['url']->forceRootUrl('https://request-host.example.test');
        $this->app['url']->forceScheme('https');

        $this->assertSame(
            'https://trusted.example.test/manual/installation',
            app(CanonicalUrlResolver::class)->documentUrl('/manual/installation'),
        );
    }

    public function test_the_configured_application_url_is_the_sitemap_origin_for_a_relative_site_base(): void
    {
        config()->set('app.url', 'https://trusted.example.test');
        config()->set('inkstone.site.base_url', '/manual');
        $this->app['url']->forceRootUrl('https://request-host.example.test');
        $this->app['url']->forceScheme('https');

        $this->assertSame(
            'https://trusted.example.test/manual/sitemap.xml',
            app(SitemapExtension::class)->url(),
        );
    }

    public function test_a_fixed_inkstone_route_domain_remains_the_canonical_origin(): void
    {
        config()->set('app.url', 'https://trusted.example.test');
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.routes.path', 'manual');
        config()->set('inkstone.routes.domain', 'docs.example.test');
        config()->set('inkstone.routes.middleware', []);
        $this->app['url']->forceRootUrl('https://request-host.example.test');
        $this->app['url']->forceScheme('https');

        \Inkstone::routes();

        $resolver = app(CanonicalUrlResolver::class);

        $this->assertSame(
            'https://docs.example.test/manual/installation',
            $resolver->documentUrl('/installation'),
        );
        $this->assertSame(
            'https://docs.example.test/manual/sitemap.xml',
            app(SitemapExtension::class)->url(),
        );
    }

    public function test_a_fixed_domain_on_the_named_route_is_preserved(): void
    {
        config()->set('app.url', 'https://trusted.example.test');
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.routes.path', 'manual');
        config()->set('inkstone.routes.domain', null);
        config()->set('inkstone.routes.middleware', []);

        $this->app['router']->get('manual/{inkstonePath?}', static fn (): string => '')
            ->domain('outer.docs.example.test')
            ->where('inkstonePath', '.*')
            ->name('inkstone.docs');
        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->assertSame(
            'outer.docs.example.test',
            app('router')->getRoutes()->getByName('inkstone.docs')?->getDomain(),
        );
        $this->assertSame(
            'https://outer.docs.example.test/manual/sitemap.xml',
            app(SitemapExtension::class)->url(),
        );
    }

    public function test_route_inference_preserves_a_trusted_application_subdirectory(): void
    {
        config()->set('app.url', 'https://trusted.example.test/application');
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.routes.path', 'manual');
        config()->set('inkstone.routes.domain', null);
        config()->set('inkstone.routes.middleware', []);

        \Inkstone::routes();

        $this->assertSame(
            'https://trusted.example.test/application/manual/sitemap.xml',
            app(SitemapExtension::class)->url(),
        );
    }

    public function test_route_inference_does_not_copy_a_request_controlled_root_path_prefix(): void
    {
        config()->set('app.url', 'https://trusted.example.test');
        config()->set('inkstone.site.base_url', '');
        config()->set('inkstone.routes.path', 'manual');
        config()->set('inkstone.routes.domain', null);
        config()->set('inkstone.routes.middleware', []);
        $this->app['url']->forceRootUrl('https://request-host.example.test/untrusted-prefix');

        \Inkstone::routes();

        $this->assertSame(
            'https://trusted.example.test/manual/sitemap.xml',
            app(SitemapExtension::class)->url(),
        );
    }

    #[DataProvider('siteBasesWithSuffixes')]
    public function test_a_site_base_with_a_query_or_fragment_is_rejected(string $baseUrl): void
    {
        config()->set('inkstone.site.base_url', $baseUrl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain a query string or fragment');

        app(SitemapExtension::class)->url();
    }

    #[DataProvider('malformedHttpUrls')]
    public function test_malformed_http_urls_are_not_accepted_as_absolute_canonical_urls(string $url): void
    {
        $this->assertFalse(app(CanonicalUrlResolver::class)->isAbsolute($url));
    }

    #[DataProvider('malformedHttpUrls')]
    public function test_sitemap_generation_rejects_malformed_http_urls(string $url): void
    {
        config()->set('inkstone.site.base_url', 'https://docs.example.test/manual');

        $context = new BuildContext(
            documents: [new Document(
                sourcePath: '/tmp/inkstone-malformed-url.md',
                relativePath: 'malformed-url.md',
                slug: 'malformed-url',
                url: $url,
            )],
            pages: [],
            outputPath: $this->outputPath,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('malformed HTTP or HTTPS URL');

        app(SitemapExtension::class)->afterBuild($context);
    }

    public function test_valid_encoded_http_url_components_are_accepted(): void
    {
        $url = 'https://docs.example.test/manual/a%20b?language=php%208.5&version=13';

        $this->assertTrue(app(CanonicalUrlResolver::class)->isAbsolute($url));
        $this->assertSame($url, app(CanonicalUrlResolver::class)->documentUrl($url));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedHttpUrls(): array
    {
        return [
            'missing host' => ['https://?missing-host'],
            'invalid host characters' => ['https://bad host.example/path'],
            'userinfo credentials' => ['https://user:secret@example.test/private'],
            'backslash authority confusion' => ['https://docs.example.test\\@attacker.test/path'],
            'embedded control character' => ["https://docs.example.test/\nspoofed"],
            'invalid hostname label' => ['https://-invalid.example.test/path'],
            'raw pipe in path' => ['https://docs.example.test/a|b'],
            'raw angle bracket in path' => ['https://docs.example.test/a<b'],
            'raw quote in path' => ['https://docs.example.test/a"b'],
            'malformed percent escape' => ['https://docs.example.test/%ZZ'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function siteBasesWithSuffixes(): array
    {
        return [
            'absolute query' => ['https://docs.example.test/manual?language=en'],
            'absolute fragment' => ['https://docs.example.test/manual#v2'],
            'relative query' => ['/manual?language=en'],
            'relative fragment' => ['/manual#v2'],
        ];
    }
}

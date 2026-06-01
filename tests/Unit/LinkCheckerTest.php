<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;
use Inkstone\Services\LinkChecker;
use Inkstone\Tests\TestCase;

final class LinkCheckerTest extends TestCase
{
    private LinkChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new LinkChecker;
    }

    public function test_it_passes_valid_internal_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/getting-started/installation',
                html: '<p>Install page</p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_reports_broken_page_link(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation">Install</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(1, $reports);
        $this->assertSame('README.md', $reports[0]->sourceFile);
        $this->assertSame('/getting-started/installation', $reports[0]->brokenHref);
        $this->assertStringContainsString('Page not found', $reports[0]->reason);
    }

    public function test_it_reports_broken_fragment_on_valid_page(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation#nonexistent">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/getting-started/installation',
                html: '<h2 id="setup">Setup</h2><h2 id="config">Config</h2>',
                headings: [
                    new Heading(2, 'Setup', 'setup', 10),
                    new Heading(2, 'Config', 'config', 20),
                ],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(1, $reports);
        $this->assertSame('README.md', $reports[0]->sourceFile);
        $this->assertSame('/getting-started/installation#nonexistent', $reports[0]->brokenHref);
        $this->assertStringContainsString('nonexistent', $reports[0]->reason);
    }

    public function test_it_passes_valid_fragment_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation#setup">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/getting-started/installation',
                html: '<h2 id="setup">Setup</h2>',
                headings: [
                    new Heading(2, 'Setup', 'setup', 10),
                ],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_passes_fragment_only_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<h1 id="intro">Introduction</h1><h2 id="setup">Setup</h2><p><a href="#intro">Intro</a><a href="#setup">Setup</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_reports_broken_fragment_only_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<h1 id="intro">Introduction</h1><p><a href="#nonexistent">Missing</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(1, $reports);
        $this->assertSame('README.md', $reports[0]->sourceFile);
        $this->assertSame('#nonexistent', $reports[0]->brokenHref);
        $this->assertStringContainsString('nonexistent', $reports[0]->reason);
    }

    public function test_it_skips_external_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="https://example.com">Example</a><a href="http://example.com">HTTP</a><a href="mailto:hello@example.com">Email</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_protocol_relative_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="//cdn.example.com/script.js">Script</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_empty_href(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="">Empty</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_github_raw_urls(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="https://raw.githubusercontent.com/vendor/package/main/LICENSE.md">License</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_reports_multiple_broken_links_in_one_document(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/missing-page">Missing</a><a href="/also-missing">Also missing</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(2, $reports);
    }

    public function test_it_reports_links_across_multiple_documents(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/page-one.md',
                relativePath: 'page-one.md',
                slug: 'page-one',
                url: '/page-one',
                html: '<p><a href="/page-two">Page Two</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/page-two.md',
                relativePath: 'page-two.md',
                slug: 'page-two',
                url: '/page-two',
                html: '<p><a href="/page-one">Page One</a><a href="/page-three">Page Three</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(1, $reports);
        $this->assertSame('page-two.md', $reports[0]->sourceFile);
        $this->assertSame('/page-three', $reports[0]->brokenHref);
    }

    public function test_it_handles_links_with_query_strings(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation?ref=home">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/getting-started/installation',
                html: '<p>Install page</p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_links_with_fragments_and_query_strings(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation?ref=home#setup">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/getting-started/installation',
                html: '<h2 id="setup">Setup</h2>',
                headings: [
                    new Heading(2, 'Setup', 'setup', 10),
                ],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_base_url_prepended_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/inkstone',
                html: '<p><a href="/inkstone/getting-started/installation">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/inkstone/getting-started/installation',
                html: '<p>Install page</p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_empty_html_documents(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_empty_document_list(): void
    {
        $reports = $this->checker->check([]);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_tel_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="tel:+1234567890">Call</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_javascript_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="javascript:void(0)">JS</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_skips_data_uris(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="data:text/plain,hello">Data</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_query_only_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="?page=2">Next</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_slash_path_links(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<h1 id="intro">Introduction</h1><p><a href="/">Home</a><a href="/#intro">Home with anchor</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_trailing_slash_in_path(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="/getting-started/installation/">Install</a></p>',
                headings: [],
            ),
            new Document(
                sourcePath: 'docs/getting-started/installation.md',
                relativePath: 'getting-started/installation.md',
                slug: 'getting-started/installation',
                url: '/getting-started/installation',
                html: '<p>Install page</p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_malformed_urls_that_break_parse_url(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<p><a href="///">Triple slash</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }

    public function test_it_handles_percent_encoded_fragments(): void
    {
        $documents = [
            new Document(
                sourcePath: 'docs/README.md',
                relativePath: 'README.md',
                slug: '',
                url: '/',
                html: '<h2 id="unicode-heading-日本語テスト">Unicode</h2><p><a href="#unicode-heading-%E6%97%A5%E6%9C%AC%E8%AA%9E%E3%83%86%E3%82%B9%E3%83%88">Unicode</a></p>',
                headings: [],
            ),
        ];

        $reports = $this->checker->check($documents);

        $this->assertCount(0, $reports);
    }
}

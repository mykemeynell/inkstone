<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\GitHubRelativeLinkTransformer;

final class GitHubRelativeLinkTransformerTest extends TestCase
{
    public function test_it_rewrites_nested_links_and_images_with_query_and_fragment(): void
    {
        $document = new Document(
            sourcePath: 'docs/guides/advanced/install.md',
            relativePath: 'guides/advanced/install.md',
            slug: 'guides/advanced/install',
            url: '/guides/advanced/install',
            html: '<p><a href="../configuration.md?plain=1#cache">Config</a><img src="../../assets/logo.png#hero"></p>',
        );

        $document = $this->transformer(['branch' => '2.x'])->transform($document);

        $this->assertStringContainsString(
            'https://raw.githubusercontent.com/vendor/package/2.x/guides/configuration.md?plain=1#cache',
            $document->html,
        );
        $this->assertStringContainsString(
            'https://raw.githubusercontent.com/vendor/package/2.x/assets/logo.png#hero',
            $document->html,
        );
    }

    public function test_it_rewrites_directory_links_to_readme_files(): void
    {
        $document = new Document(
            sourcePath: 'docs/guides/advanced/index.md',
            relativePath: 'guides/advanced/index.md',
            slug: 'guides/advanced',
            url: '/guides/advanced',
            html: '<p><a href="./">Current README</a><a href="../">Parent README</a></p>',
        );

        $document = $this->transformer(['tag' => 'v1.0.0'])->transform($document);

        $this->assertStringContainsString(
            'https://raw.githubusercontent.com/vendor/package/v1.0.0/guides/advanced/README.md',
            $document->html,
        );
        $this->assertStringContainsString(
            'https://raw.githubusercontent.com/vendor/package/v1.0.0/guides/README.md',
            $document->html,
        );
    }

    public function test_it_respects_disabled_rewrite_options_and_non_relative_urls(): void
    {
        $document = new Document(
            sourcePath: 'docs/readme.md',
            relativePath: 'README.md',
            slug: '',
            url: '/',
            html: '<p><a href="LICENSE.md">License</a><a href="/docs">Docs</a><a href="#intro">Intro</a><img src="assets/logo.png"></p>',
        );

        $document = $this->transformer([
            'rewrite_relative_links' => false,
            'rewrite_images' => true,
        ])->transform($document);

        $this->assertStringContainsString('href="LICENSE.md"', $document->html);
        $this->assertStringContainsString('href="/docs"', $document->html);
        $this->assertStringContainsString('href="#intro"', $document->html);
        $this->assertStringContainsString('https://raw.githubusercontent.com/vendor/package/main/assets/logo.png', $document->html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function transformer(array $overrides = []): GitHubRelativeLinkTransformer
    {
        return new GitHubRelativeLinkTransformer(array_replace([
            'repository' => 'git@github.com:vendor/package.git',
            'branch' => 'main',
            'rewrite_relative_links' => true,
            'rewrite_images' => true,
        ], $overrides));
    }
}

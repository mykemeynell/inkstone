<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\DTOs\Document;
use Inkstone\Pipelines\TransformerPipeline;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\ExternalLinkTransformer;
use Inkstone\Transformers\GitHubRelativeLinkTransformer;
use Inkstone\Transformers\HeadingAnchorTransformer;
use Inkstone\Transformers\SyntaxHighlightTransformer;

final class TransformerPipelineTest extends TestCase
{
    public function test_it_adds_heading_anchors_external_link_attrs_and_github_urls(): void
    {
        $document = new Document(
            sourcePath: 'docs/getting-started/install.md',
            relativePath: 'getting-started/install.md',
            slug: 'getting-started/install',
            url: '/docs/getting-started/install',
            html: '<h2>Install</h2><p><a href="LICENSE.md">License</a> <a href="https://example.com">External</a><img src="../assets/logo.png"></p>',
        );

        $document = (new HeadingAnchorTransformer)->transform($document);
        $document = (new GitHubRelativeLinkTransformer([
            'repository' => 'https://github.com/vendor/package',
            'branch' => 'main',
            'rewrite_relative_links' => true,
            'rewrite_images' => true,
        ]))->transform($document);
        $document = (new ExternalLinkTransformer)->transform($document);

        $this->assertStringContainsString('id="install"', $document->html);
        $this->assertStringContainsString('https://raw.githubusercontent.com/vendor/package/main/getting-started/LICENSE.md', $document->html);
        $this->assertStringContainsString('https://raw.githubusercontent.com/vendor/package/main/assets/logo.png', $document->html);
        $this->assertStringContainsString('target="_blank"', $document->html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $document->html);
    }

    public function test_it_adds_code_block_filename_and_highlight_metadata(): void
    {
        $document = new Document(
            sourcePath: 'docs/example.md',
            relativePath: 'example.md',
            slug: 'example',
            url: '/docs/example',
            markdown: "```php filename=example.php {2}\necho 'one';\necho 'two';\n```\n",
            html: "<pre><code class=\"language-php\">echo 'one';\necho 'two';\n</code></pre>\n",
        );

        $document = (new SyntaxHighlightTransformer([
            'enabled' => true,
            'copy_button' => true,
            'show_line_numbers' => true,
        ]))->transform($document);

        $this->assertStringContainsString('data-filename="example.php"', $document->html);
        $this->assertStringContainsString('data-highlight-lines="2"', $document->html);
        $this->assertStringContainsString('data-line="2"', $document->html);
        $this->assertStringContainsString('is-highlighted', $document->html);
        $this->assertStringContainsString('class="token"', $document->html);
    }

    public function test_provider_loads_transformers_from_configuration(): void
    {
        config()->set('inkstone.transformers', [
            ExternalLinkTransformer::class,
        ]);

        $pipeline = app(TransformerPipeline::class);
        $document = $pipeline->process(new Document(
            sourcePath: 'docs/example.md',
            relativePath: 'example.md',
            slug: 'example',
            url: '/docs/example',
            html: '<h2>Example</h2><a href="https://example.com">External</a>',
        ));

        $this->assertStringContainsString('target="_blank"', $document->html);
        $this->assertStringNotContainsString('id="example"', $document->html);
    }
}

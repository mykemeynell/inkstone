<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Demos\DemoRendererRegistry;
use Inkstone\Demos\SimpleDemoRuntime;
use Inkstone\DTOs\Document;
use Inkstone\Parsers\CommonMarkMarkdownParser;
use Inkstone\Tests\TestCase;
use Inkstone\Transformers\DemoBlockTransformer;
use Inkstone\Transformers\SyntaxHighlightTransformer;

final class DemoRenderingTest extends TestCase
{
    public function test_expected_exceptions_are_rendered_without_failing(): void
    {
        $document = $this->transform(
            "```demo:php throws\nthrow new \\RuntimeException('Expected failure');\n```\n",
            '<pre><code class="language-demo:php">throw new \RuntimeException(&#039;Expected failure&#039;);</code></pre>',
        );

        $this->assertStringContainsString('inkstone-demo', $document->html);
        $this->assertStringContainsString('RuntimeException: Expected failure', $document->html);
    }

    public function test_void_demo_output_can_be_suppressed(): void
    {
        $document = $this->transform(
            "```demo:php void\nusleep(1);\n```\n",
            '<pre><code class="language-demo:php">usleep(1);</code></pre>',
        );

        $this->assertStringContainsString('inkstone-demo-output"></div>', $document->html);
    }

    public function test_sandbox_rejects_disallowed_functions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Demo uses disallowed function [file_put_contents].');

        $this->transform(
            "```demo:php\nfile_put_contents('unsafe.txt', 'no');\n```\n",
            '<pre><code class="language-demo:php">file_put_contents(&#039;unsafe.txt&#039;, &#039;no&#039;);</code></pre>',
        );
    }

    public function test_markdown_demos_render_nested_markdown_output(): void
    {
        $document = new Document(
            sourcePath: 'docs/markdown.md',
            relativePath: 'markdown.md',
            slug: 'markdown',
            url: '/docs/markdown',
            markdown: "````demo:markdown\n```php\necho 'Nested';\n```\n````\n",
        );

        $document = (new CommonMarkMarkdownParser)->parse($document);
        $document = $this->demoTransformer()->transform($document);
        $document = (new SyntaxHighlightTransformer)->transform($document);

        $this->assertStringContainsString('inkstone-demo', $document->html);
        $this->assertStringContainsString('inkstone-demo-source', $document->html);
        $this->assertStringContainsString('inkstone-demo-output', $document->html);
        $this->assertStringContainsString('data-copyable="true"', $document->html);
        $this->assertStringContainsString('language-php', $document->html);
    }

    private function transform(string $markdown, string $html): Document
    {
        $transformer = $this->demoTransformer();

        return $transformer->transform(new Document(
            sourcePath: 'docs/demo.md',
            relativePath: 'demo.md',
            slug: 'demo',
            url: '/docs/demo',
            markdown: $markdown,
            html: $html,
        ));
    }

    private function demoTransformer(): DemoBlockTransformer
    {
        return new DemoBlockTransformer(
            new SimpleDemoRuntime([
                'enabled' => true,
                'describe_void_output' => false,
                'sandbox' => [
                    'enabled' => true,
                    'timeout' => 1,
                    'memory_limit' => '128M',
                ],
            ]),
            new DemoRendererRegistry,
            [
                'enabled' => true,
                'describe_void_output' => false,
            ],
        );
    }
}

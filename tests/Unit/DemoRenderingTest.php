<?php

declare(strict_types=1);

namespace Inkstone\Tests\Unit;

use Inkstone\Demos\DemoRendererRegistry;
use Inkstone\Demos\DemoRuntimeResolver;
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
        $this->assertStringContainsString('data-inkstone-demo-tab="source"', $document->html);
        $this->assertStringContainsString('data-inkstone-demo-tab="output"', $document->html);
        $this->assertStringNotContainsString('data-inkstone-demo-collapse', $document->html);
        $this->assertStringContainsString('pre data-copyable="true"', $document->html);
    }

    public function test_void_demo_output_can_be_suppressed(): void
    {
        $document = $this->transform(
            "```demo:php void\nusleep(1);\n```\n",
            '<pre><code class="language-demo:php">usleep(1);</code></pre>',
        );

        $this->assertStringContainsString('data-inkstone-demo-panel="output" hidden></div>', $document->html);
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

    public function test_sandbox_timeout_fails_unexpected_infinite_loops(): void
    {
        if (! function_exists('pcntl_alarm')) {
            $this->markTestSkipped('pcntl is required for demo timeout enforcement.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected demo exception: RuntimeException Demo execution exceeded 1 seconds.');

        $this->transform(
            "```demo:php\nwhile (true) {}\n```\n",
            '<pre><code class="language-demo:php">while (true) {}</code></pre>',
        );
    }

    public function test_sandbox_rejects_process_execution_functions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Demo uses disallowed function [shell_exec].');

        $this->transform(
            "```demo:php\nshell_exec('whoami');\n```\n",
            '<pre><code class="language-demo:php">shell_exec(&#039;whoami&#039;);</code></pre>',
        );
    }

    public function test_expected_exception_stack_traces_can_be_rendered_as_expandable_details(): void
    {
        $document = $this->demoTransformer(transformerConfig: ['show_stack_traces' => true])->transform(new Document(
            sourcePath: 'docs/demo.md',
            relativePath: 'demo.md',
            slug: 'demo',
            url: '/docs/demo',
            markdown: "```demo:php throws\nthrow new \\RuntimeException('Expected failure');\n```\n",
            html: '<pre><code class="language-demo:php">throw new \RuntimeException(&#039;Expected failure&#039;);</code></pre>',
        ));

        $this->assertStringContainsString('inkstone-demo-stack', $document->html);
        $this->assertStringContainsString('<summary>Stack trace</summary>', $document->html);
    }

    public function test_php_demos_can_use_a_disposable_sqlite_database(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for disposable demo databases.');
        }

        $previousDefault = config('database.default');

        $document = $this->demoTransformer(runtimeConfig: [
            'use_disposable_database' => true,
        ])->transform(new Document(
            sourcePath: 'docs/database-demo.md',
            relativePath: 'database-demo.md',
            slug: 'database-demo',
            url: '/docs/database-demo',
            markdown: <<<'MARKDOWN'
```demo:php
\Illuminate\Support\Facades\Schema::create('widgets', function ($table) {
    $table->id();
    $table->string('name');
});

\Illuminate\Support\Facades\DB::table('widgets')->insert(['name' => 'Inkstone']);

return \Illuminate\Support\Facades\DB::table('widgets')->value('name');
```
MARKDOWN,
            html: '<pre><code class="language-demo:php">\Illuminate\Support\Facades\Schema::create(&#039;widgets&#039;, function ($table) {
    $table-&gt;id();
    $table-&gt;string(&#039;name&#039;);
});

\Illuminate\Support\Facades\DB::table(&#039;widgets&#039;)-&gt;insert([&#039;name&#039; =&gt; &#039;Inkstone&#039;]);

return \Illuminate\Support\Facades\DB::table(&#039;widgets&#039;)-&gt;value(&#039;name&#039;);
</code></pre>',
        ));

        $this->assertSame($previousDefault, config('database.default'));
        $this->assertStringContainsString('Inkstone', $document->html);
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

    public function test_runtime_resolver_maps_static_and_php_alias_languages(): void
    {
        $resolver = new DemoRuntimeResolver([
            'execute_php' => true,
            'execute_blade' => false,
        ]);

        $this->assertSame('php', $resolver->resolve('eloquent'));
        $this->assertSame('php', $resolver->resolve('collections'));
        $this->assertSame('php', $resolver->resolve('service-container'));
        $this->assertSame('static', $resolver->resolve('blade'));
        $this->assertSame('static', $resolver->resolve('vue'));
        $this->assertSame('static', $resolver->resolve('react'));
        $this->assertSame('static', $resolver->resolve('livewire'));
    }

    public function test_static_demo_runtimes_render_source_as_static_output(): void
    {
        $document = $this->transform(
            "```demo:vue\n<script setup>\nconst name = 'Inkstone';\n</script>\n```\n",
            '<pre><code class="language-demo:vue">&lt;script setup&gt;
const name = &#039;Inkstone&#039;;
&lt;/script&gt;</code></pre>',
        );

        $this->assertStringContainsString('data-demo-language="vue"', $document->html);
        $this->assertStringContainsString('&lt;script setup&gt;', $document->html);
    }

    public function test_demo_blocks_are_matched_to_rendered_code_blocks_when_examples_contain_demo_fences(): void
    {
        $document = new Document(
            sourcePath: 'docs/demo-order.md',
            relativePath: 'demo-order.md',
            slug: 'demo-order',
            url: '/docs/demo-order',
            markdown: <<<'MARKDOWN'
````markdown
```demo:php
collect(['Laravel', 'Docs', 'Inkstone'])
    ->all();
```
````

```demo:php
collect(['Laravel', 'Docs', 'Inkstone'])
    ->map(fn ($item) => strtoupper($item))
    ->all();
```

````demo:markdown
```php
echo 'Nested code stays intact';
```
````
MARKDOWN,
        );

        $document = (new CommonMarkMarkdownParser)->parse($document);
        $document = $this->demoTransformer()->transform($document);

        $this->assertStringContainsString('LARAVEL', $document->html);
        $this->assertStringContainsString('Nested code stays intact', $document->html);
        $this->assertLessThan(
            strpos($document->html, 'Nested code stays intact') ?: PHP_INT_MAX,
            strpos($document->html, 'LARAVEL') ?: PHP_INT_MAX,
        );
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

    /**
     * @param  array<string, mixed>  $transformerConfig
     * @param  array<string, mixed>  $runtimeConfig
     */
    private function demoTransformer(array $transformerConfig = [], array $runtimeConfig = []): DemoBlockTransformer
    {
        return new DemoBlockTransformer(
            new SimpleDemoRuntime(array_replace_recursive([
                'enabled' => true,
                'describe_void_output' => false,
                'use_disposable_database' => false,
                'sandbox' => [
                    'enabled' => true,
                    'timeout' => 1,
                    'memory_limit' => '128M',
                    'allow_filesystem_writes' => false,
                    'allow_process_execution' => false,
                ],
            ], $runtimeConfig), new DemoRuntimeResolver(array_replace_recursive([
                'execute_php' => true,
                'execute_blade' => true,
            ], $runtimeConfig))),
            new DemoRendererRegistry,
            array_replace([
                'enabled' => true,
                'describe_void_output' => false,
            ], $transformerConfig),
        );
    }
}

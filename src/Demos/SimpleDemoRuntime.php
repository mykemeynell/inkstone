<?php

declare(strict_types=1);

namespace Inkstone\Demos;

use Illuminate\Support\HtmlString;
use Inkstone\Contracts\DemoRuntime;
use Inkstone\DTOs\DemoBlock;
use Inkstone\DTOs\DemoResult;
use Inkstone\DTOs\Document;
use Inkstone\Parsers\CommonMarkMarkdownParser;
use RuntimeException;
use Throwable;

final class SimpleDemoRuntime implements DemoRuntime
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function run(DemoBlock $block): DemoResult
    {
        if ($block->language === 'markdown' || $block->language === 'md') {
            return new DemoResult($block, true, value: $this->htmlString($this->renderMarkdown($block->code)));
        }

        if ($block->language === 'html') {
            return new DemoResult($block, true, value: $this->htmlString($block->code));
        }

        if ($block->language === 'blade') {
            try {
                return new DemoResult($block, true, value: $this->renderBlade($block->code));
            } catch (Throwable $throwable) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }

                $expected = $this->isExpected($block, $throwable);

                return new DemoResult($block, $expected, exception: $throwable, expectedException: $expected);
            }
        }

        if ($block->language !== 'php') {
            return new DemoResult($block, true, value: null);
        }

        $this->assertSandboxAllows($block->code);

        $previousMemoryLimit = ini_get('memory_limit');
        $timeout = (int) data_get($this->config, 'sandbox.timeout', 5);
        $memoryLimit = (string) data_get($this->config, 'sandbox.memory_limit', '128M');

        try {
            if ($memoryLimit !== '') {
                ini_set('memory_limit', $memoryLimit);
            }

            if (function_exists('pcntl_async_signals') && function_exists('pcntl_alarm')) {
                pcntl_async_signals(true);
                pcntl_signal(SIGALRM, static function () use ($timeout): never {
                    throw new RuntimeException("Demo execution exceeded {$timeout} seconds.");
                });
                pcntl_alarm($timeout);
            }

            ob_start();
            $value = $this->evaluate($block->code);
            $stdout = (string) ob_get_clean();

            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            return new DemoResult($block, true, $block->voidOutput ? null : $value, $stdout);
        } catch (Throwable $throwable) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            $expected = $this->isExpected($block, $throwable);

            return new DemoResult($block, $expected, exception: $throwable, expectedException: $expected);
        } finally {
            ini_set('memory_limit', $previousMemoryLimit);
        }
    }

    private function evaluate(string $code): mixed
    {
        $code = trim($code);

        if ($this->looksLikeExpression($code)) {
            $code = 'return '.rtrim($code, ';').';';
        }

        return (static function () use ($code): mixed {
            return eval($code);
        })();
    }

    private function renderMarkdown(string $markdown): string
    {
        $parser = new CommonMarkMarkdownParser((array) config('inkstone.markdown', []));
        $document = $parser->parse(new Document(
            sourcePath: 'demo.md',
            relativePath: 'demo.md',
            slug: 'demo',
            url: '#',
            markdown: $markdown,
        ));

        return $document->html;
    }

    private function renderBlade(string $template): mixed
    {
        if (! (bool) data_get($this->config, 'execute_blade', true)) {
            return null;
        }

        if (function_exists('app') && app()->bound('blade.compiler')) {
            $compiler = app('blade.compiler');
            $php = $compiler->compileString($template);

            ob_start();
            (static function () use ($php): void {
                eval('?>'.$php);
            })();

            return $this->htmlString((string) ob_get_clean());
        }

        return null;
    }

    private function htmlString(string $html): mixed
    {
        if (class_exists(HtmlString::class)) {
            return new HtmlString($html);
        }

        return $html;
    }

    private function looksLikeExpression(string $code): bool
    {
        return ! str_contains($code, 'return ')
            && ! str_contains($code, 'echo ')
            && substr_count($code, ';') <= 1;
    }

    private function assertSandboxAllows(string $code): void
    {
        if (! (bool) data_get($this->config, 'sandbox.enabled', true)) {
            return;
        }

        $disallowed = [
            'exec',
            'passthru',
            'proc_open',
            'shell_exec',
            'system',
            'file_put_contents',
            'fopen',
            'unlink',
            'rmdir',
            'mkdir',
        ];

        foreach ($disallowed as $function) {
            if (preg_match('/\b'.preg_quote($function, '/').'\s*\(/i', $code)) {
                throw new RuntimeException("Demo uses disallowed function [{$function}].");
            }
        }
    }

    private function isExpected(DemoBlock $block, Throwable $throwable): bool
    {
        foreach ($block->expectedExceptions as $expectedException) {
            if ($expectedException === Throwable::class || $throwable instanceof $expectedException) {
                return true;
            }
        }

        return false;
    }
}

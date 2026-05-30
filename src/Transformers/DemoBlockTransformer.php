<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMDocumentFragment;
use DOMElement;
use Inkstone\Contracts\DemoRuntime;
use Inkstone\Contracts\Transformer;
use Inkstone\Demos\DemoRendererRegistry;
use Inkstone\DTOs\DemoBlock;
use Inkstone\DTOs\DemoResult;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;
use RuntimeException;
use Throwable;

final class DemoBlockTransformer implements Transformer
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly DemoRuntime $runtime,
        private readonly DemoRendererRegistry $renderers,
        private readonly array $config = [],
    ) {}

    public function transform(Document $document): Document
    {
        if ($document->html === '' || ! (bool) ($this->config['enabled'] ?? true)) {
            return $document;
        }

        $blocks = $this->demoBlocksFromMarkdown($document->markdown);

        if ($blocks === []) {
            return $document;
        }

        $fragment = HtmlDocument::fromFragment($document->html);
        $codeBlocks = $fragment->xpath()->query('//pre/code[starts-with(@class, "language-demo:")]');
        $index = 0;

        foreach ($codeBlocks as $code) {
            if (! $code instanceof DOMElement || ! $code->parentNode instanceof DOMElement || ! isset($blocks[$index])) {
                continue;
            }

            $result = $this->runtime->run($blocks[$index]);

            if (! $result->successful && $result->exception !== null) {
                throw new RuntimeException(
                    'Unexpected demo exception: '.$result->exception::class.' '.$result->exception->getMessage(),
                    previous: $result->exception,
                );
            }

            $html = $this->renderDemo($result);
            $replacement = $this->createFragment($fragment->document, $html);
            $code->parentNode->parentNode?->replaceChild($replacement, $code->parentNode);
            $index++;
        }

        return $document->withHtml($fragment->toHtml());
    }

    /**
     * @return list<DemoBlock>
     */
    private function demoBlocksFromMarkdown(string $markdown): array
    {
        $lines = preg_split('/\R/', $markdown);

        if (! is_array($lines)) {
            return [];
        }

        $blocks = [];
        $count = count($lines);

        for ($index = 0; $index < $count; $index++) {
            $line = $lines[$index];

            if (! preg_match('/^[ \t]{0,3}(?<fence>`{3,}|~{3,})demo:(?<language>[A-Za-z0-9_-]+)(?<metadata>[^\r\n]*)$/', $line, $match)) {
                $skippedTo = $this->skipNonDemoFence($lines, $index);

                if ($skippedTo !== null) {
                    $index = $skippedTo;
                }

                continue;
            }

            $fence = (string) $match['fence'];
            $code = [];

            for ($index++; $index < $count; $index++) {
                if ($this->isClosingFence($lines[$index], $fence)) {
                    break;
                }

                $code[] = $lines[$index];
            }

            $blocks[] = $this->createBlock([
                'language' => (string) $match['language'],
                'metadata' => (string) $match['metadata'],
                'code' => implode("\n", $code).($code !== [] ? "\n" : ''),
            ]);
        }

        return $blocks;
    }

    /**
     * @param  list<string>  $lines
     */
    private function skipNonDemoFence(array $lines, int $index): ?int
    {
        if (! preg_match('/^[ \t]{0,3}(?<fence>`{3,}|~{3,})(?!demo:).*$/', $lines[$index], $match)) {
            return null;
        }

        $count = count($lines);
        $fence = (string) $match['fence'];

        for ($index++; $index < $count; $index++) {
            if ($this->isClosingFence($lines[$index], $fence)) {
                return $index;
            }
        }

        return $count - 1;
    }

    private function isClosingFence(string $line, string $openingFence): bool
    {
        $character = $openingFence[0];
        $minimumLength = strlen($openingFence);

        return (bool) preg_match('/^[ \t]{0,3}'.preg_quote($character, '/').'{'.$minimumLength.',}[ \t]*$/', $line);
    }

    /**
     * @param  array<int|string, string>  $match
     */
    private function createBlock(array $match): DemoBlock
    {
        $metadata = trim((string) $match['metadata']);
        $expectedExceptions = [];
        $voidOutput = false;

        foreach (preg_split('/\s+/', $metadata) ?: [] as $attribute) {
            if ($attribute === '') {
                continue;
            }

            if ($attribute === 'void') {
                $voidOutput = true;
            }

            if ($attribute === 'throws') {
                $expectedExceptions[] = Throwable::class;
            }

            if (str_starts_with($attribute, 'throws:')) {
                foreach (explode(',', substr($attribute, 7)) as $class) {
                    $class = trim($class);

                    if ($class !== '' && is_a($class, Throwable::class, true)) {
                        $expectedExceptions[] = $class;
                    }
                }
            }
        }

        return new DemoBlock(
            language: strtolower((string) $match['language']),
            code: (string) $match['code'],
            metadata: ['raw' => $metadata],
            expectedExceptions: $expectedExceptions,
            voidOutput: $voidOutput,
        );
    }

    private function renderDemo(DemoResult $result): string
    {
        $block = $result->block;
        $value = $result->exception ?? $result->value;
        $output = $result->stdout !== ''
            ? '<pre><code class="language-text">'.e($result->stdout).'</code></pre>'
            : ($block->voidOutput && ! (bool) ($this->config['describe_void_output'] ?? false)
                ? ''
                : $this->renderers->render($value));

        if ($result->exception !== null && (bool) ($this->config['show_stack_traces'] ?? false)) {
            $output .= '<details class="inkstone-demo-stack"><summary>Stack trace</summary><pre><code class="language-text">'.e($result->exception->getTraceAsString()).'</code></pre></details>';
        }

        return '<div class="inkstone-demo" data-inkstone-demo data-demo-language="'.e($block->language).'">'
            .'<div class="inkstone-demo-toolbar">'
            .'<div class="inkstone-demo-tabs" role="tablist" aria-label="Demo panels">'
            .'<button type="button" class="is-active" role="tab" aria-selected="true" data-inkstone-demo-tab="source">Source</button>'
            .'<button type="button" role="tab" aria-selected="false" data-inkstone-demo-tab="output">Output</button>'
            .'</div>'
            .'</div>'
            .'<div class="inkstone-demo-panel inkstone-demo-source is-active" role="tabpanel" data-inkstone-demo-panel="source"><pre data-copyable="true"><code class="language-'.e($block->language).'">'.e($block->code).'</code></pre></div>'
            .'<div class="inkstone-demo-panel inkstone-demo-output" role="tabpanel" data-inkstone-demo-panel="output" hidden>'.$output.'</div>'
            .'</div>'
            .'</div>';
    }

    private function createFragment(\DOMDocument $document, string $html): DOMDocumentFragment
    {
        $fragment = $document->createDocumentFragment();
        $source = HtmlDocument::fromFragment($html);

        foreach ($source->root()->childNodes as $child) {
            $fragment->appendChild($document->importNode($child, true));
        }

        return $fragment;
    }
}

<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMDocumentFragment;
use DOMElement;
use Inkstone\Contracts\DemoRuntime;
use Inkstone\Contracts\Transformer;
use Inkstone\Demos\DemoRendererRegistry;
use Inkstone\DTOs\DemoBlock;
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

            $html = $this->renderDemo($blocks[$index], $result->exception ?? $result->value, $result->stdout);
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
        preg_match_all('/^(?<fence>`{3,}|~{3,})demo:(?<language>[A-Za-z0-9_-]+)(?<metadata>[^\r\n]*)\R(?<code>.*?)^\k<fence>[ \t]*$/ms', $markdown, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match): DemoBlock => $this->createBlock($match), $matches);
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

    private function renderDemo(DemoBlock $block, mixed $value, string $stdout): string
    {
        $output = $stdout !== ''
            ? '<pre><code class="language-text">'.e($stdout).'</code></pre>'
            : ($block->voidOutput && ! (bool) ($this->config['describe_void_output'] ?? false)
                ? ''
                : $this->renderers->render($value));

        return '<div class="inkstone-demo" data-demo-language="'.e($block->language).'">'
            .'<div class="inkstone-demo-tabs">'
            .'<div class="inkstone-demo-source"><pre><code class="language-'.e($block->language).'">'.e($block->code).'</code></pre></div>'
            .'<div class="inkstone-demo-output">'.$output.'</div>'
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

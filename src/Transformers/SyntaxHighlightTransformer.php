<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMElement;
use DOMText;
use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;
use Phiki\Phiki;
use Phiki\Theme\Theme;
use Throwable;

final class SyntaxHighlightTransformer implements Transformer
{
    private Phiki $phiki;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = [])
    {
        $this->phiki = new Phiki;
    }

    public function transform(Document $document): Document
    {
        if ($document->html === '' || ! (bool) ($this->config['enabled'] ?? true)) {
            return $document;
        }

        $fragment = HtmlDocument::fromFragment($document->html);
        $blocks = $fragment->xpath()->query('//pre/code');
        $metadata = $this->codeBlockMetadata($document->markdown);
        $index = 0;

        foreach ($blocks as $code) {
            if (! $code instanceof DOMElement || ! $code->parentNode instanceof DOMElement) {
                continue;
            }

            $pre = $code->parentNode;
            $language = $this->languageFromCode($code);
            $consumeMetadata = ! $this->isGeneratedDemoOutput($pre);
            $blockMetadata = $consumeMetadata ? ($metadata[$index] ?? []) : [];

            if (str_starts_with($language, 'demo:')) {
                if ($consumeMetadata) {
                    $index++;
                }

                continue;
            }

            if ($this->usesPhiki()) {
                $highlighted = $this->highlightedPre($fragment, $code->textContent, $language);

                if ($highlighted instanceof DOMElement) {
                    $pre->parentNode?->replaceChild($highlighted, $pre);
                    $pre = $highlighted;
                    $code = $this->firstCodeChild($pre) ?? $code;
                }
            }

            $this->decoratePre($pre);

            if ((bool) ($this->config['copy_button'] ?? true)) {
                $pre->setAttribute('data-copyable', 'true');
            }

            if ((bool) ($this->config['show_line_numbers'] ?? true)) {
                $pre->setAttribute('data-line-numbers', 'true');
            }

            if (trim($code->getAttribute('class')) === '') {
                $code->setAttribute('class', 'language-'.$language);
            }

            if (is_string($blockMetadata['filename'] ?? null) && $blockMetadata['filename'] !== '') {
                $pre->setAttribute('data-filename', $blockMetadata['filename']);
                $pre->setAttribute('class', trim($pre->getAttribute('class').' has-filename'));
            }

            if (is_array($blockMetadata['highlighted_lines'] ?? null) && $blockMetadata['highlighted_lines'] !== []) {
                $pre->setAttribute('data-highlight-lines', implode(',', $blockMetadata['highlighted_lines']));
                $this->highlightLines($code, $blockMetadata['highlighted_lines']);
            }

            if ($consumeMetadata) {
                $index++;
            }
        }

        return $document->withHtml($fragment->toHtml());
    }

    private function usesPhiki(): bool
    {
        return ($this->config['driver'] ?? 'phiki') === 'phiki';
    }

    private function highlightedPre(HtmlDocument $target, string $code, string $language): ?DOMElement
    {
        try {
            $highlighted = HtmlDocument::fromFragment(
                $this->phiki
                    ->codeToHtml(rtrim($code, "\r\n"), $this->phikiLanguage($language), $this->phikiTheme())
                    ->toString()
            );
        } catch (Throwable) {
            return null;
        }

        $pre = $highlighted->xpath()->query('//pre')->item(0);

        if (! $pre instanceof DOMElement) {
            return null;
        }

        $imported = $target->document->importNode($pre, true);

        return $imported instanceof DOMElement ? $imported : null;
    }

    private function phikiLanguage(string $language): string
    {
        $aliases = [
            'text' => 'txt',
            'shell' => 'bash',
        ];

        $language = $aliases[$language] ?? $language;

        return $this->phiki->environment->grammars->has($language) ? $language : 'txt';
    }

    private function phikiTheme(): string|array|Theme
    {
        $configured = $this->config['theme'] ?? null;

        if (is_string($configured) || $configured instanceof Theme || $this->isThemeMap($configured)) {
            return $configured;
        }

        return [
            'light' => Theme::GithubLight,
            'dark' => Theme::GithubDark,
        ];
    }

    private function themeFromString(string $theme): string|Theme
    {
        foreach (Theme::cases() as $case) {
            if ($case->value === $theme) {
                return $case;
            }
        }

        return $theme;
    }

    private function isThemeMap(mixed $value): bool
    {
        if (! is_array($value) || $value === []) {
            return false;
        }

        foreach ($value as $theme) {
            if (! is_string($theme) && ! $theme instanceof Theme) {
                return false;
            }
        }

        return true;
    }

    private function languageFromCode(DOMElement $code): string
    {
        if (preg_match('/(?:^|\s)language-([^\s]+)/', $code->getAttribute('class'), $match) === 1) {
            return strtolower($match[1]);
        }

        return 'text';
    }

    private function firstCodeChild(DOMElement $pre): ?DOMElement
    {
        foreach ($pre->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'code') {
                return $child;
            }
        }

        return null;
    }

    private function isGeneratedDemoOutput(DOMElement $pre): bool
    {
        $node = $pre->parentNode;

        while ($node instanceof DOMElement) {
            if (str_contains(' '.$node->getAttribute('class').' ', ' inkstone-demo-output ')) {
                return true;
            }

            $node = $node->parentNode;
        }

        return false;
    }

    private function decoratePre(DOMElement $pre): void
    {
        $classes = array_filter(explode(' ', $pre->getAttribute('class')));
        $classes[] = 'inkstone-code-block';
        $pre->setAttribute('class', implode(' ', array_values(array_unique($classes))));
    }

    /**
     * @return list<array{filename?: string, highlighted_lines?: list<int>}>
     */
    private function codeBlockMetadata(string $markdown): array
    {
        preg_match_all('/^(?<fence>`{3,}|~{3,})(?<info>[^\r\n]*)\R(?<code>.*?)^\k<fence>[ \t]*$/ms', $markdown, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match): array => $this->metadataFromInfo((string) $match['info']), $matches);
    }

    /**
     * @return array{filename?: string, highlighted_lines?: list<int>}
     */
    private function metadataFromInfo(string $info): array
    {
        $metadata = [];

        if (preg_match('/(?:filename|file|title)=("[^"]+"|\'[^\']+\'|[^\s]+)/', $info, $match) === 1) {
            $metadata['filename'] = trim($match[1], '"\'');
        }

        if (preg_match('/\{(?<lines>[\d,\-\s]+)\}/', $info, $match) === 1) {
            $metadata['highlighted_lines'] = $this->parseHighlightedLines($match['lines']);
        } elseif (preg_match('/highlight(?:_lines)?=("[^"]+"|\'[^\']+\'|[^\s]+)/', $info, $match) === 1) {
            $metadata['highlighted_lines'] = $this->parseHighlightedLines(trim($match[1], '"\''));
        }

        return $metadata;
    }

    /**
     * @return list<int>
     */
    private function parseHighlightedLines(string $value): array
    {
        $lines = [];

        foreach (explode(',', str_replace(' ', '', $value)) as $part) {
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part, 2));

                if ($start > 0 && $end >= $start) {
                    array_push($lines, ...range($start, $end));
                }

                continue;
            }

            $line = (int) $part;

            if ($line > 0) {
                $lines[] = $line;
            }
        }

        return array_values(array_unique($lines));
    }

    /**
     * @param  list<int>  $highlightedLines
     */
    private function highlightLines(DOMElement $code, array $highlightedLines): void
    {
        $lineNumber = 0;

        foreach ($code->getElementsByTagName('span') as $line) {
            if (! str_contains(' '.$line->getAttribute('class').' ', ' line ')) {
                continue;
            }

            $lineNumber++;
            $line->setAttribute('data-line', (string) $lineNumber);

            if (in_array($lineNumber, $highlightedLines, true)) {
                $line->setAttribute('class', trim($line->getAttribute('class').' is-highlighted'));
            }
        }

        if ($lineNumber > 0) {
            return;
        }

        $this->wrapCodeLines($code, $highlightedLines);
    }

    /**
     * @param  list<int>  $highlightedLines
     */
    private function wrapCodeLines(DOMElement $code, array $highlightedLines): void
    {
        $text = $code->textContent;

        while ($code->firstChild !== null) {
            $code->removeChild($code->firstChild);
        }

        $lines = preg_split('/\R/', rtrim($text, "\r\n")) ?: [];
        $lastLine = count($lines) - 1;

        foreach ($lines as $lineNumber => $line) {
            $number = $lineNumber + 1;
            $span = $code->ownerDocument->createElement('span');
            $span->setAttribute('class', in_array($number, $highlightedLines, true) ? 'line is-highlighted' : 'line');
            $span->setAttribute('data-line', (string) $number);
            $span->appendChild(new DOMText($line));
            $code->appendChild($span);

            if ($lineNumber < $lastLine) {
                $code->appendChild(new DOMText("\n"));
            }
        }
    }
}

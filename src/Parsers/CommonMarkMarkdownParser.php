<?php

declare(strict_types=1);

namespace Inkstone\Parsers;

use Inkstone\Contracts\MarkdownParser;
use Inkstone\DTOs\Document;
use Inkstone\DTOs\Heading;
use Inkstone\Support\Slugger;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Yaml\Yaml;

final class CommonMarkMarkdownParser implements MarkdownParser
{
    private MarkdownConverter $converter;

    private Slugger $slugger;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = [])
    {
        $environment = new Environment([
            'html_input' => $this->config['html_input'] ?? 'allow',
            'allow_unsafe_links' => (bool) ($this->config['unsafe_links'] ?? false),
            'renderer' => $this->config['renderer'] ?? ['soft_break' => "\n"],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FootnoteExtension);

        $this->converter = new MarkdownConverter($environment);
        $this->slugger = new Slugger;
    }

    public function parse(Document $document): Document
    {
        [$metadata, $markdown] = $this->extractFrontmatter($document->markdown);
        $headings = $this->extractHeadings($markdown);
        $rendered = $this->converter->convert($markdown);

        return $document
            ->withMarkdown($markdown)
            ->withParsedContent($rendered->getContent(), $metadata, $headings, $rendered->getDocument());
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function extractFrontmatter(string $markdown): array
    {
        if (! preg_match('/\A---\R(?<yaml>.*?)\R---\R?/s', $markdown, $matches)) {
            return [[], $markdown];
        }

        $metadata = Yaml::parse((string) $matches['yaml']);

        if (! is_array($metadata)) {
            $metadata = [];
        }

        return [$metadata, substr($markdown, strlen($matches[0]))];
    }

    /**
     * @return list<Heading>
     */
    private function extractHeadings(string $markdown): array
    {
        preg_match_all('/^(?<marks>#{1,6})[ \t]+(?<text>.+?)\s*#*\s*$/m', $markdown, $matches, PREG_SET_ORDER);

        $headings = [];
        $ids = [];

        foreach ($matches as $index => $match) {
            $text = trim((string) preg_replace('/[`*_~\[\]\(\)]/', '', $match['text']));
            $base = $this->slugger->slug($text);
            $id = $base;
            $suffix = 2;

            while (isset($ids[$id])) {
                $id = $base.'-'.$suffix;
                $suffix++;
            }

            $ids[$id] = true;
            $headings[] = new Heading(strlen($match['marks']), $text, $id, $index);
        }

        return $headings;
    }
}

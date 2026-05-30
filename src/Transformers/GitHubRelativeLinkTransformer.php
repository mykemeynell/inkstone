<?php

declare(strict_types=1);

namespace Inkstone\Transformers;

use DOMElement;
use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;
use Inkstone\Support\HtmlDocument;
use Inkstone\Support\PathNormalizer;

final class GitHubRelativeLinkTransformer implements Transformer
{
    private PathNormalizer $normalizer;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = [])
    {
        $this->normalizer = new PathNormalizer;
    }

    public function transform(Document $document): Document
    {
        if ($document->html === '' || $this->repositoryPath() === null) {
            return $document;
        }

        $fragment = HtmlDocument::fromFragment($document->html);

        if ((bool) ($this->config['rewrite_relative_links'] ?? true)) {
            $links = $fragment->xpath()->query('//a[@href]');

            foreach ($links as $link) {
                if ($link instanceof DOMElement) {
                    $this->rewriteAttribute($link, 'href', $document);
                }
            }
        }

        if ((bool) ($this->config['rewrite_images'] ?? true)) {
            $images = $fragment->xpath()->query('//img[@src]');

            foreach ($images as $image) {
                if ($image instanceof DOMElement) {
                    $this->rewriteAttribute($image, 'src', $document);
                }
            }
        }

        return $document->withHtml($fragment->toHtml());
    }

    private function rewriteAttribute(DOMElement $element, string $attribute, Document $document): void
    {
        $value = $element->getAttribute($attribute);

        if (! $this->isRelative($value)) {
            return;
        }

        $element->setAttribute($attribute, $this->rawUrlFor($value, $document));
    }

    private function isRelative(string $value): bool
    {
        if ($value === '' || str_starts_with($value, '#') || str_starts_with($value, '/')) {
            return false;
        }

        return ! preg_match('/^[a-z][a-z0-9+.-]*:/i', $value);
    }

    private function rawUrlFor(string $target, Document $document): string
    {
        $parts = parse_url($target);
        $path = $this->normalizeTargetPath((string) ($parts['path'] ?? ''));
        $suffix = '';

        if (isset($parts['query'])) {
            $suffix .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $suffix .= '#'.$parts['fragment'];
        }

        $directory = trim(dirname($document->relativePath), '.');
        $resolved = $this->normalizer->normalize(($directory !== '' ? $directory.'/' : '').$path);

        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s%s',
            $this->repositoryPath(),
            $this->ref(),
            $resolved,
            $suffix,
        );
    }

    private function normalizeTargetPath(string $path): string
    {
        if ($path === '' || $path === '.' || str_ends_with($path, '/')) {
            return rtrim($path, '/').'/README.md';
        }

        return $path;
    }

    private function ref(): string
    {
        $ref = $this->config['ref'] ?? $this->config['tag'] ?? $this->config['branch'] ?? 'main';

        return trim((string) $ref, '/');
    }

    private function repositoryPath(): ?string
    {
        $repository = trim((string) ($this->config['repository'] ?? ''));

        if ($repository === '') {
            return null;
        }

        if (preg_match('/github\.com[:\/](?<path>[^\/]+\/[^\/]+?)(?:\.git)?(?:$|[?#])/i', $repository, $matches)) {
            return trim($matches['path'], '/');
        }

        $path = trim((string) (parse_url($repository, PHP_URL_PATH) ?: ''), '/');
        $path = preg_replace('/\.git$/', '', $path) ?? $path;

        return $path !== '' ? $path : null;
    }
}

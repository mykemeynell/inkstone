---
title: Extension Points
order: 4
---

# Extension Points

Inkstone is built from small services connected by contracts and DTOs.

Commands should stay thin. Package behavior lives in services, parsers, transformers, renderers, and generators.

## Pipeline Overview

```text
DocumentDiscoverer
    -> MarkdownParser
    -> TransformerPipeline
    -> NavigationBuilder
    -> DocumentRenderer
    -> StaticSiteGenerator
```

## Core DTOs

| DTO | Purpose |
| --- | --- |
| `Document` | Source path, relative path, slug, URL, Markdown, HTML, metadata, headings, AST |
| `Heading` | Heading level, text, ID, and position |
| `NavigationItem` | Sidebar title, URL, active state, order, children, headings |
| `RenderedPage` | Rendered document, HTML, and output path |
| `SearchEntry` | Static search index entry |
| `DemoBlock` | Parsed demo language, source, metadata, expected exceptions, void flag |
| `DemoResult` | Demo execution result, stdout, exception, rendered value state |

## Core Contracts

| Contract | Responsibility |
| --- | --- |
| `DocumentDiscoverer` | Find Markdown files and create `Document` DTOs |
| `MarkdownParser` | Parse frontmatter, headings, Markdown HTML, and AST |
| `Transformer` | Transform a parsed `Document` |
| `NavigationBuilder` | Build sidebar navigation for a document set |
| `DocumentRenderer` | Render a page through the theme |
| `StaticSiteGenerator` | Build the complete static site |
| `SearchIndexer` | Produce `SearchEntry` DTOs |
| `DemoRuntime` | Execute or render demo blocks |
| `DemoResultRenderer` | Render demo result values as HTML |

## Default Services

| Service | Contract |
| --- | --- |
| `FilesystemDocumentDiscoverer` | `DocumentDiscoverer` |
| `CommonMarkMarkdownParser` | `MarkdownParser` |
| `NavigationBuilder` | `NavigationBuilder` |
| `BladeDocumentRenderer` | `DocumentRenderer` |
| `StaticDocumentationGenerator` | `StaticSiteGenerator` |
| `JsonSearchIndexer` | `SearchIndexer` |
| `SimpleDemoRuntime` | `DemoRuntime` |

## Transformers

Default transformers are configured in order:

```php
'transformers' => [
    HeadingAnchorTransformer::class,
    ExternalLinkTransformer::class,
    GitHubRelativeLinkTransformer::class,
    DemoBlockTransformer::class,
    SyntaxHighlightTransformer::class,
],
```

Each transformer implements:

```php
use Inkstone\Contracts\Transformer;
use Inkstone\DTOs\Document;

final class AlertTransformer implements Transformer
{
    public function transform(Document $document): Document
    {
        return $document->withHtml($document->html);
    }
}
```

Register it in the config:

```php
'transformers' => [
    HeadingAnchorTransformer::class,
    AlertTransformer::class,
    SyntaxHighlightTransformer::class,
],
```

## Replacing A Service

Inside Laravel, bind your implementation in a service provider:

```php
use Inkstone\Contracts\SearchIndexer;

$this->app->bind(SearchIndexer::class, CustomSearchIndexer::class);
```

Standalone usage can load custom classes through Composer autoloading and config.

## Demo Result Renderers

Inkstone includes individual renderers for exceptions, renderables, primitives, arrays, collections, models, and objects.

Custom result rendering should implement:

```php
use Inkstone\Contracts\DemoResultRenderer;

final class MoneyResultRenderer implements DemoResultRenderer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Money;
    }

    public function render(mixed $value): string
    {
        return '<p>'.$value->format().'</p>';
    }
}
```

Keep renderers small and deterministic because they run during static builds.

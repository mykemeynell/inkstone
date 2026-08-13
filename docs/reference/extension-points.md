---
title: Extension Points
order: 4
---

# Extension Points

Inkstone is built from small services connected by contracts and DTOs.

Commands should stay thin. Package behavior lives in services, parsers, transformers, renderers, and generators.

## Pipeline Overview

```text
CompositeDocumentDiscoverer
    ├── FilesystemDocumentDiscoverer (Markdown files)
    └── ApiSpecDiscoverer (OpenAPI spec files)
         -> MarkdownParser (skipped for API documents)
         -> TransformerPipeline
             ├── HeadingAnchorTransformer
             ├── ExternalLinkTransformer
             ├── BaseUrlLinkTransformer
             ├── GitHubRelativeLinkTransformer
             ├── DemoBlockTransformer
             ├── SyntaxHighlightTransformer
             └── ApiHtmlTransformer (generates API page HTML)
         -> NavigationBuilder
         -> DocumentRenderer
         -> StaticSiteGenerator
             -> pages, search, robots metadata, and assets
             -> BuildExtensionPipeline
                 -> configured BuildExtension instances
```

## Core DTOs

| DTO | Purpose |
| --- | --- |
| `Document` | Source path, relative path, slug, URL, Markdown, HTML, metadata, headings, AST |
| `Heading` | Heading level, text, ID, and position |
| `NavigationItem` | Sidebar title, URL, active state, order, children, headings |
| `RenderedPage` | Rendered document, HTML, and output path |
| `SearchEntry` | Static search index entry |
| `BuildContext` | Readonly processed documents, rendered pages, and output path passed to post-build extensions |
| `DemoBlock` | Parsed demo language, source, metadata, expected exceptions, void flag |
| `DemoResult` | Demo execution result, stdout, exception, rendered value state |

## Core Contracts

| Contract | Responsibility |
| --- | --- |
| `DocumentDiscoverer` | Find documentation sources and create `Document` DTOs |
| `MarkdownParser` | Parse frontmatter, headings, Markdown HTML, and AST |
| `Transformer` | Transform a parsed `Document` |
| `NavigationBuilder` | Build sidebar navigation for a document set |
| `DocumentRenderer` | Render a page through the theme |
| `StaticSiteGenerator` | Build the complete static site |
| `SearchIndexer` | Produce `SearchEntry` DTOs |
| `BuildExtension` | Run application-specific work after the core build is complete |
| `DemoRuntime` | Execute or render demo blocks |
| `DemoResultRenderer` | Render demo result values as HTML |

## Default Services

| Service | Contract |
| --- | --- |
| `CompositeDocumentDiscoverer` | `DocumentDiscoverer` (aggregates all discoverers) |
| `FilesystemDocumentDiscoverer` | Markdown file discovery (child of composite) |
| `ApiSpecDiscoverer` | OpenAPI spec file discovery (child of composite) |
| `CommonMarkMarkdownParser` | `MarkdownParser` |
| `NavigationBuilder` | `NavigationBuilder` |
| `BladeDocumentRenderer` | `DocumentRenderer` |
| `StaticDocumentationGenerator` | `StaticSiteGenerator` |
| `JsonSearchIndexer` | `SearchIndexer` |
| `BuildExtensionPipeline` | Resolve and invoke configured `BuildExtension` services |
| `SitemapExtension` | `BuildExtension` |
| `SimpleDemoRuntime` | `DemoRuntime` |

## Build Extensions

Build extensions add post-build behavior without modifying the generator. Each extension implements one lifecycle method:

```php
use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;

final class BuildManifestExtension implements BuildExtension
{
    public function afterBuild(BuildContext $context): void
    {
        // Inspect $context->documents and $context->pages, then write an artifact
        // beneath $context->outputPath.
    }
}
```

`BuildContext` is readonly and exposes:

| Property | Type | Description |
| --- | --- | --- |
| `documents` | `list<Document>` | Fully processed Markdown and API documents |
| `pages` | `list<RenderedPage>` | Pages rendered and written by the build |
| `outputPath` | `string` | Configured generated-output directory |

Extensions run once, in configuration order, after pages, search, robots metadata, and assets are complete. The container resolves each class only when the extension pipeline runs, so constructor injection is available.

`StaticSiteGenerator::build()` continues to return the rendered pages. Extensions receive the same page list through `BuildContext`.

### Add An Application Extension

For example, an application can add a small JSON build manifest:

```php
namespace App\Inkstone;

use Illuminate\Filesystem\Filesystem;
use Inkstone\Contracts\BuildExtension;
use Inkstone\DTOs\BuildContext;

final readonly class BuildManifestExtension implements BuildExtension
{
    public function __construct(private Filesystem $files) {}

    public function afterBuild(BuildContext $context): void
    {
        $manifest = json_encode(
            ['pages' => count($context->pages)],
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
        );

        $this->files->put($context->outputPath.'/build-manifest.json', $manifest."\n");
    }
}
```

Register it after the bundled sitemap extension:

```php
use App\Inkstone\BuildManifestExtension;
use Inkstone\Extensions\SitemapExtension;

'extensions' => [
    SitemapExtension::class,
    BuildManifestExtension::class,
],
```

The same configuration works in a standalone `inkstone.php` loaded with `--config` when Composer can autoload the extension class. An exception from any extension fails the build through the existing command error handling.

### Bundled Sitemap Extension

`SitemapExtension` is the first bundled extension and remains in the default configuration. It preserves the existing `build.generate_sitemap` switch and `sitemap.xml` output. Laravel applications can resolve it directly to obtain the public sitemap URL:

```php
use Inkstone\Extensions\SitemapExtension;

$url = app(SitemapExtension::class)->url();
```

See [Sitemaps](/features/sitemaps) for canonical URL resolution and parent sitemap-index integration.

## Transformers

Default transformers are configured in order:

```php
'transformers' => [
    HeadingAnchorTransformer::class,
    ExternalLinkTransformer::class,
    BaseUrlLinkTransformer::class,
    GitHubRelativeLinkTransformer::class,
    DemoBlockTransformer::class,
    SyntaxHighlightTransformer::class,
    ApiHtmlTransformer::class,
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

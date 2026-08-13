---
title: Build Output
order: 2
---

# Build Output

Inkstone writes deployable static documentation output.

By default:

```text
build/docs
```

## Output Structure

A typical build looks like:

```text
build/docs/
  index.html
  search-index.json
  sitemap.xml
  robots.txt
  assets/
    css/
      inkstone.css
      themes/
        default.css
        light.css
        dark.css
        ember.css
        forest.css
    js/
      inkstone.js
  getting-started/
    installation/
      index.html
```

## Pages

Each Markdown document becomes an HTML page.

With pretty URLs enabled:

```text
docs/getting-started/installation.md
```

becomes:

```text
build/docs/getting-started/installation/index.html
```

With pretty URLs disabled, the same document becomes:

```text
build/docs/getting-started/installation.html
```

## Root Page

The root page is discovered from:

```text
docs/README.md
docs/index.md
```

and written to:

```text
build/docs/index.html
```

## Assets

Inkstone copies package CSS and JavaScript assets into:

```text
build/docs/assets
```

When `build.asset_hashing` is enabled and a Vite manifest exists, generated pages reference the hashed files from the manifest instead of the source asset names. Inkstone copies the public build output into `build/docs` and does not publish the private `.vite/manifest.json` file.

The package build uses:

```bash
npm run build
```

By default this writes production assets to `resources/dist`. If you publish or customize the asset build, point Inkstone at your generated files:

```php
'build' => [
    'assets' => [
        'dist_path' => base_path('resources/dist'),
        'manifest_path' => base_path('resources/dist/.vite/manifest.json'),
    ],
],
```

Additional asset directories are configured with:

```php
'build' => [
    'assets' => [
        'additional_paths' => [
            resource_path('docs-assets'),
        ],
    ],
],
```

## Search Index

When search is enabled, Inkstone writes:

```text
build/docs/search-index.json
```

The default theme fetches this file in the browser.

## Sitemap And Robots

When enabled, Inkstone writes:

```text
build/docs/sitemap.xml
build/docs/robots.txt
```

These files are useful for public documentation sites.

`sitemap.xml` is produced by the default `SitemapExtension`, while `robots.txt` remains core build metadata. The existing `build.generate_sitemap` setting controls sitemap generation.

Sitemap locations use the processed document URLs, including OpenAPI pages and `.html` paths when pretty URLs are disabled. Inkstone removes duplicate locations and enforces the single-sitemap protocol limits of 50,000 URLs and 50 MB uncompressed.

Set `site.base_url` to an absolute deployment URL for static hosting. Laravel can resolve a relative base against the application URL or infer the configured Inkstone route. When no origin is available, Inkstone retains relative locations, emits one non-fatal warning, and leaves the build successful; production builds should supply an absolute base. See [Sitemaps](/features/sitemaps) for URL resolution and parent sitemap-index integration.

## Extension Artifacts

Configured build extensions run after the core output above is complete. An extension can inspect the processed documents and rendered pages, then write application-specific artifacts under the output path. See [Extension Points](/reference/extension-points).

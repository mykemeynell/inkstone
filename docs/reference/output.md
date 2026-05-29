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

Additional asset directories are configured with:

```php
'assets' => [
    'additional_paths' => [
        resource_path('docs-assets'),
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

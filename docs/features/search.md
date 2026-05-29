---
title: Search
order: 3
---

# Search

Inkstone generates a static search index during the build.

The default index path is:

```text
build/docs/search-index.json
```

## Indexed Fields

Each entry contains:

- title
- URL
- excerpt
- normalized text content
- headings

Example shape:

```json
{
  "title": "Installation",
  "url": "/getting-started/installation",
  "excerpt": "Install Inkstone with Composer...",
  "content": "Install Inkstone with Composer...",
  "headings": [
    {
      "level": 2,
      "text": "Requirements",
      "id": "requirements"
    }
  ]
}
```

## Client-Side Search

Search runs in the browser. No database, queue, server route, or external search service is required.

The default theme:

- sorts results by relevance
- gives page title matches high priority
- handles small spelling mistakes
- highlights exact search terms
- renders previews as HTML
- limits visible results for a compact search panel

## Configuration

```php
'search' => [
    'enabled' => true,
    'driver' => 'fuse',
    'index_path' => 'search-index.json',
    'include_headings' => true,
    'include_content' => true,
    'max_content_length' => 5000,
],
```

## Disable Search

```php
'search' => [
    'enabled' => false,
],
```

When disabled, Inkstone does not write `search-index.json` and the default theme does not render the search input.

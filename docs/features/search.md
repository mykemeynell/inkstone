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

## Search Trigger

The search trigger has two modes controlled by `search.type`:

```php
'search' => [
    'type' => 'input', // 'input' (default) or 'button'
],
```

| Mode | Behavior |
|---|---|
| `input` | Shows a search box with placeholder text and a `⌘K` badge in the header |
| `button` | Shows a magnifying glass icon button |

Both modes respond to the `⌘K` / `Ctrl+K` global keyboard shortcut.

## Client-Side Search

Search runs in the browser. No database, queue, server route, or external search service is required.

The default theme:

- opens search from the header trigger or `⌘K` / `Ctrl+K`
- sorts results by relevance
- gives page title matches high priority
- handles small spelling mistakes
- highlights exact search terms
- renders previews as HTML
- limits visible results for a compact search panel

## Search Drivers

Inkstone ships with four search drivers:

| Driver | Type | Description |
| --- | --- | --- |
| `json` | Local | Default static index used by the built-in search UI. |
| `lunr` | Local | Generates a Lunr.js compatible index file. |
| `algolia` | Remote | Pushes indexed documents directly to an Algolia index. |
| `typesense` | Remote | Pushes indexed documents directly to a Typesense collection. |

### JSON Driver (Default)

The default configuration uses the `json` driver:

```php
'search' => [
    'enabled' => true,
    'driver' => 'json',
    'max_content_length' => 5000,
    'drivers' => [
        'json' => [
            'driver' => JsonSearchIndexer::class,
            'config' => [
                'index_path' => 'search-index.json',
            ],
        ],
    ],
],
```

### Lunr Driver

Generates a `lunr-index.json` file and loads the Lunr.js library in the frontend.

```php
'search' => [
    'driver' => 'lunr',
],
```

### Algolia Driver

Pushes records to Algolia during the build.

```php
'search' => [
    'driver' => 'algolia',
    'drivers' => [
        'algolia' => [
            'driver' => AlgoliaSearchIndexer::class,
            'config' => [
                'app_id' => env('INKSTONE_ALGOLIA_APP_ID'),
                'api_key' => env('INKSTONE_ALGOLIA_SEARCH_KEY'),
                'index_name' => env('INKSTONE_ALGOLIA_INDEX_NAME'),
            ],
        ],
    ],
],
```

### Typesense Driver

Pushes records to a Typesense server during the build.

```php
'search' => [
    'driver' => 'typesense',
    'drivers' => [
        'typesense' => [
            'driver' => TypesenseSearchIndexer::class,
            'config' => [
                'server' => [
                    'nodes' => [
                        [
                            'host' => env('INKSTONE_TYPESENSE_HOST', 'localhost'),
                            'port' => env('INKSTONE_TYPESENSE_PORT', '8108'),
                            'protocol' => env('INKSTONE_TYPESENSE_PROTOCOL', 'http'),
                        ],
                    ],
                    'api_key' => env('INKSTONE_TYPESENSE_SEARCH_KEY'),
                    'collection_name' => env('INKSTONE_TYPESENSE_COLLECTION_NAME'),
                ],
            ],
        ],
    ],
],
```

The built-in theme automatically switches its frontend implementation based on the configured `INKSTONE_SEARCH_DRIVER`. Remote drivers require the appropriate API keys to be set during the `docs:build` process.

Search drivers are loaded as separate JavaScript files and registered via a `window.InkstoneSearchDriver` interface. Custom drivers can implement `init(index, config, utils)`, `search(query, index, config, utils)`, `preview(entry, query, utils)`, and `highlight(text, query, utils)` to replace or extend the default search behavior.

## Disable Search

```php
'search' => [
    'enabled' => false,
],
```

When disabled, Inkstone does not write a search index and the default theme does not render the search button.

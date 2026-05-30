---
title: Performance
order: 6
---

# Performance

Inkstone builds static output from the filesystem. The main cost centers are document discovery, Markdown parsing, transformation, rendering, asset copying, and search indexing.

## Filesystem Work

Document discovery ignores hidden directories, `vendor`, `node_modules`, and configured ignored paths. Keep generated output outside the source docs directory so builds do not scan their own output.

Static asset copying is split into:

- package CSS and JavaScript fallback assets
- Vite-built versioned assets when a manifest exists
- configured additional asset directories

## Parser And Transformer Work

Markdown is parsed once per document, then passed through the configured transformer pipeline. Keep custom transformers focused on the current document and avoid repeated full-site filesystem scans inside transformers.

GitHub rewriting, heading anchors, syntax highlighting, and demo rendering all run at build time. Demo blocks should remain small and deterministic.

## Search Indexing

The default JSON search index stores title, URL, headings, excerpt, and truncated content. Use `search.drivers.json.config.max_content_length` to cap index size for large documentation sites.

```php
'search' => [
    'driver' => 'json',
    'drivers' => [
        'json' => [
            'config' => [
                'max_content_length' => 5000,
            ],
        ],
    ],
],
```

## Verification Commands

Use these commands to catch regressions:

```bash
composer test
composer lint
composer analyze
npm run build
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

For larger sites, run the build command against a representative docs directory and compare page count, output size, and build time before release.

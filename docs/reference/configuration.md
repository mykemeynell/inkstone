---
title: Configuration Reference
order: 3
---

# Configuration Reference

This page lists the main `inkstone` configuration keys.

## Paths

```php
'source_path' => base_path('docs'),
'output_path' => base_path('build/docs'),
```

## Site

```php
'site' => [
    'title' => env('INKSTONE_TITLE', 'Inkstone Docs'),
    'description' => env('INKSTONE_DESCRIPTION', 'Project documentation generated with Inkstone.'),
    'base_url' => env('INKSTONE_BASE_URL', ''),
    'favicon' => null,
    'logo' => [
        'light' => null,
        'dark' => null,
    ],
    'show_title' => true,
    'footer' => [
        'enabled' => true,
        'text' => 'Built with Inkstone',
        'url' => 'https://github.com/mykemeynell/inkstone',
    ],
],
```

`base_url` defaults to the served root. Set it only when the generated site is mounted below a URL path such as `/docs` or `/my-package`.

`favicon` and `logo` can be explicit URLs or `null` for source-level discovery. `logo` accepts a string or an array with `light` and `dark` keys for separate logo variants per theme mode.

The footer can be disabled or customized from `site.footer`. Set `site.show_title` to `false` to hide the site title from the header brand.

## Theme

```php
'theme' => [
    'name' => 'default',
    'layout' => 'default',
    'default_mode' => 'system',
    'syntax_highlighting' => [
        'enabled' => true,
        'theme' => [
            'light' => \Phiki\Theme\Theme::GithubLight,
            'dark' => \Phiki\Theme\Theme::GithubDark,
        ],
        'show_line_numbers' => true,
        'copy_button' => true,
    ],
],
```

`theme.name` selects the CSS variable file loaded after the base stylesheet. `theme.layout` selects the Blade layout view. `theme.default_mode` sets the initial color mode (`system`, `light`, or `dark`).

Syntax highlighting is powered by **Phiki** and occurs at build time.

## Markdown

```php
'markdown' => [
    'unsafe_links' => false,
    'html_input' => 'allow',
    'renderer' => [
        'soft_break' => "\n",
    ],
],
```

Inkstone registers CommonMark core, GitHub Flavored Markdown, and footnotes.

## Navigation

```php
'navigation' => [
    'expanded' => [],
],
```

Navigation ordering comes from frontmatter `order`. The `expanded` key controls which sidebar sections start expanded: set to `true` (all expanded), `false` (only the active section), or an array of slugs to expand specific sections.

## Discovery

```php
'discovery' => [
    'ignore' => [
        'vendor',
        'node_modules',
        'build',
        'storage',
    ],
],
```

Inkstone discovers `.md` and `.markdown` files recursively.

## Search

```php
'search' => [
    'enabled' => true,
    'driver' => env('INKSTONE_SEARCH_DRIVER', 'json'),
    'max_content_length' => 5000,
    'type' => 'input',
    'drivers' => [
        'json' => [ ... ],
        'lunr' => [ ... ],
        'algolia' => [ ... ],
        'typesense' => [ ... ],
    ],
],
```

`search.type` controls the search trigger UI: `'input'` shows a search box with keyboard shortcut badge, `'button'` shows an icon button.

Inkstone supports multiple search drivers. Local drivers (`json`, `lunr`) generate an index file, while remote drivers (`algolia`, `typesense`) push data directly to their respective APIs.

## GitHub

```php
'github' => [
    'repository' => env('INKSTONE_GITHUB_REPOSITORY', '...'),
    'branch' => env('INKSTONE_GITHUB_BRANCH', 'main'),
    'rewrite_relative_links' => true,
    'rewrite_images' => true,
],
```

Relative repository links are rewritten to raw GitHub URLs.

## Build

```php
'build' => [
    'clean_output_before_build' => true,
    'pretty_urls' => true,
    'generate_sitemap' => true,
    'generate_robots_txt' => true,
    'asset_hashing' => true,
    'assets' => [
        'additional_paths' => [ ... ],
        'dist_path' => null,
        'manifest_path' => null,
    ],
],
```

## Demos

```php
'demos' => [
    'enabled' => true,
    'execute_php' => true,
    'execute_blade' => true,
    'describe_void_output' => false,
    'use_disposable_database' => false,
    'database' => [
        'connection' => 'inkstone_demo',
        'database' => ':memory:',
    ],
    'show_stack_traces' => false,
    'sandbox' => [
        'enabled' => true,
        'timeout' => 5,
        'memory_limit' => '128M',
        'allow_filesystem_writes' => false,
        'allow_process_execution' => false,
    ],
],
```

Demo blocks are static build-time examples. `describe_void_output` controls whether void demo output is described textually. `use_disposable_database` creates an in-memory SQLite database per demo. `database` configures the database connection. `show_stack_traces` controls stack trace rendering for demo exceptions.

## Local Server

```php
'server' => [
    'host' => '127.0.0.1',
    'port' => 8080,
],
```

Used by `docs:serve`.

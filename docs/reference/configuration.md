---
title: Configuration Reference
order: 3
---

# Configuration Reference

This page lists the main `inkstone` configuration keys.

## Paths

```php
'docs_path' => base_path('docs'),
'output_path' => base_path('build/docs'),
```

## Site

```php
'site' => [
    'title' => env('DOCS_TITLE', 'Inkstone Documentation'),
    'description' => env('DOCS_DESCRIPTION', 'Project documentation generated with Inkstone.'),
    'base_url' => env('DOCS_BASE_URL', ''),
    'favicon' => null,
    'logo' => null,
    'footer' => [
        'enabled' => true,
        'text' => 'Built with Inkstone',
        'url' => 'https://github.com/mykemeynell/inkstone',
    ],
],
```

`base_url` defaults to the served root. Set it only when the generated site is mounted below a URL path such as `/docs` or `/my-package`.

`favicon` and `logo` can be explicit URLs or `null` for source-level discovery.

The footer can be disabled or customized from `site.footer`.

## Theme

```php
'theme' => [
    'name' => 'default',
    'available' => ['default', 'light', 'dark', 'ember', 'forest'],
    'dark_mode' => true,
    'default_mode' => 'system',
    'show_table_of_contents' => true,
    'code_block_theme' => [
        'light' => Theme::GithubLight,
        'dark' => Theme::GithubDark,
    ],
],
```

`theme.name` selects the CSS variable file loaded after the base stylesheet.

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
    'auto_generate' => true,
    'sort' => 'frontmatter',
    'fallback_sort' => 'alphabetical',
    'max_depth' => 10,
],
```

Navigation ordering comes from frontmatter `order`.

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
    'driver' => 'fuse',
    'index_path' => 'search-index.json',
    'include_headings' => true,
    'include_content' => true,
    'max_content_length' => 5000,
],
```

Search is static and client-side.

## GitHub

```php
'github' => [
    'repository' => env('DOCS_GITHUB_REPOSITORY', 'https://github.com/vendor/package'),
    'branch' => env('DOCS_GITHUB_BRANCH', 'main'),
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
    'minify_html' => false,
    'asset_hashing' => true,
],
```

## Syntax Highlighting

```php
'syntax_highlighting' => [
    'enabled' => true,
    'driver' => 'phiki',
    'theme_light' => 'github-light',
    'theme_dark' => 'github-dark',
    'show_line_numbers' => true,
    'copy_button' => true,
],
```

Phiki is used at build time.

## Demos

```php
'demos' => [
    'enabled' => true,
    'execute_php' => true,
    'execute_blade' => true,
    'describe_void_output' => false,
    'sandbox' => [
        'enabled' => true,
        'timeout' => 5,
        'memory_limit' => '128M',
        'allow_filesystem_writes' => false,
        'allow_process_execution' => false,
    ],
],
```

Demo blocks are static build-time examples.

## Local Server

```php
'server' => [
    'host' => '127.0.0.1',
    'port' => 8080,
    'watch' => true,
    'open_browser' => true,
],
```

Used by `docs:serve`.

# Inkstone

Inkstone generates static documentation sites from Markdown for Laravel projects and standalone PHP package repositories.

[View the Demo Documentation](https://mykemeynell.github.io/inkstone/)

## Install

```bash
composer require mykemeynell/inkstone --dev
```

## Standalone Usage

Use the package binary when the target project is not a full Laravel application:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Useful options:

- `--source=docs` sets the Markdown source directory.
- `--output=build/docs` sets the generated static site directory.
- `--base-url=/docs` sets the base URL when the generated site is mounted below a subdirectory.
- `--config=inkstone.php` loads an optional PHP config file and merges it over the defaults.

## Laravel Usage

Inside a Laravel application, use the Artisan commands registered by the service provider:

```bash
php artisan docs:install
php artisan docs:build
php artisan docs:serve
php artisan docs:clean
```

`docs:install` publishes Inkstone configuration, starter docs, theme assets, and deployment examples. `docs:build` writes deployable static HTML into `build/docs` by default.

## Configuration

Inkstone uses `config/inkstone.php` inside Laravel applications. In standalone package repositories, create `inkstone.php` or `config/inkstone.php` in the package root.

```php
<?php

use Phiki\Theme\Theme;

return [
    'source_path' => __DIR__.'/docs',
    'output_path' => __DIR__.'/build/docs',

    'site' => [
        'title' => env('INKSTONE_TITLE', 'Package Documentation'),
        'description' => env('INKSTONE_DESCRIPTION', 'Static documentation site.'),
        'base_url' => env('INKSTONE_BASE_URL', ''),
    ],

    'theme' => [
        'syntax_highlighting' => [
            'enabled' => true,
            'theme' => [
                'light' => Theme::GithubLight,
                'dark' => Theme::GithubDark,
            ],
        ],
    ],

    'search' => [
        'enabled' => true,
        'driver' => env('INKSTONE_SEARCH_DRIVER', 'json'),
    ],

    'github' => [
        'repository' => env('INKSTONE_GITHUB_REPOSITORY', 'https://github.com/vendor/package'),
        'branch' => env('INKSTONE_GITHUB_BRANCH', 'main'),
    ],
];
```

## Build Output

Generated sites use pretty URLs by default:

```text
build/docs/index.html
build/docs/installation/index.html
build/docs/search-index.json
```

The output can be deployed to GitHub Pages, Cloudflare Pages, Netlify, Vercel, or any static host.

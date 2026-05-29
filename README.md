# Inkstone

Inkstone generates static documentation sites from Markdown for Laravel projects and standalone PHP package repositories.

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

You can generate docs for another package by running the command from that package root, or by passing absolute paths:

```bash
vendor/bin/inkstone docs:build \
  --source=/path/to/package/docs \
  --output=/path/to/package/build/docs
```

## Laravel Usage

Inside a Laravel application, use the Artisan commands registered by the service provider:

```bash
php artisan docs:install
php artisan docs:build
php artisan docs:serve
php artisan docs:clean
```

`docs:install` publishes Inkstone configuration, starter docs, theme assets, and deployment examples. `docs:build` writes deployable static HTML into `build/docs` by default.

Inkstone also ships optional Laravel Boost resources:

```text
resources/boost/guidelines/core.blade.php
resources/boost/skills/inkstone-documentation/SKILL.md
```

If your Laravel app uses Boost, run `php artisan boost:install` or `php artisan boost:update` after installing Inkstone so Boost can include the Inkstone guidance and documentation skill. Boost is not required for standalone or Artisan builds.

## Configuration

Inkstone uses `config/inkstone.php` inside Laravel applications. In standalone package repositories, create `inkstone.php` or `config/inkstone.php` in the package root.

```php
<?php

return [
    'docs_path' => __DIR__.'/docs',
    'output_path' => __DIR__.'/build/docs',
    'site' => [
        'title' => 'Package Documentation',
        'base_url' => '',
    ],
    'github' => [
        'repository' => 'https://github.com/vendor/package',
        'branch' => 'main',
    ],
];
```

## Build Output

Generated sites use pretty URLs:

```text
build/docs/index.html
build/docs/installation/index.html
build/docs/search-index.json
```

The output can be deployed to GitHub Pages, Cloudflare Pages, Netlify, Vercel, or any static host.

By default, generated links and assets assume `build/docs` is served as the web root. Set `DOCS_BASE_URL` or pass `--base-url` only when the site is mounted below a subdirectory.

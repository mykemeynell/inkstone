---
title: Laravel Usage
order: 3
---

# Laravel Usage

When Inkstone is installed in a Laravel application, its commands are registered by `Inkstone\Providers\InkstoneServiceProvider` and run through Artisan.

## Install Starter Files

```bash
php artisan docs:install
```

The install command can publish:

- `config/inkstone.php`
- starter Markdown files
- default Blade theme views
- frontend source assets
- deployment examples
- `build/.gitignore`

Existing files are not overwritten unless you pass `--force`:

```bash
php artisan docs:install --force
```

## Configure The Package

Publish the config file:

```bash
php artisan vendor:publish --tag=inkstone-config
```

Edit:

```text
config/inkstone.php
```

The most common keys are:

- `source_path`
- `output_path`
- `site`
- `theme`
- `github`
- `search`
- `demos`

## Build Static Documentation

```bash
php artisan docs:build
```

The build command discovers documents, parses Markdown, transforms HTML, renders the theme, copies assets, writes static pages, and generates search and metadata files.

## Serve Locally

```bash
php artisan docs:serve --host=127.0.0.1 --port=8080
```

The serve command runs PHP's built-in static server against the generated output directory. Use it for local preview, not production hosting.

## Clean Generated Output

```bash
php artisan docs:clean
```

The clean command removes the configured generated output directory and recreates it.

## Override Paths Per Command

Artisan commands also accept path options:

```bash
php artisan docs:build --source=resources/docs --output=build/product-docs --base-url=/product-docs
```

These options override the current config for that command run.

## Laravel Boost

Inkstone ships optional Laravel Boost package resources:

```text
resources/boost/guidelines/core.blade.php
resources/boost/skills/inkstone-documentation/SKILL.md
```

If the Laravel application uses Boost, refresh Boost after installing or updating Inkstone:

```bash
php artisan boost:install
php artisan boost:update
```

Boost can then include Inkstone-aware guidance for documentation structure, commands, navigation ordering, static demo blocks, and standalone package usage.

Boost is optional. Inkstone commands work normally without it.

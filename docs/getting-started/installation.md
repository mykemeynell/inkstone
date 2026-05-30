---
title: Installation
order: 1
---

# Installation

Install Inkstone with Composer:

```bash
composer require mykemeynell/inkstone --dev
```

Inkstone is normally installed as a development dependency because it generates deployable static files during documentation builds.

## Requirements

- PHP 8.2 or newer
- Composer
- Laravel 11, 12, or 13 when using the Artisan workflow
- No full Laravel application when using the standalone CLI

## Verify The Binary

After Composer installs the package, the standalone binary should be available:

```bash
vendor/bin/inkstone list
```

You should see:

```text
docs:install
docs:build
docs:serve
docs:clean
```

## Install Starter Files

For Laravel applications, use:

```bash
php artisan docs:install
```

For standalone package repositories, use:

```bash
vendor/bin/inkstone docs:install --source=docs --output=build/docs
```

The install command writes starter documentation, theme files, deploy examples, and a `build/.gitignore` file. Existing files are preserved unless you pass `--force`.

## Build Static Docs

Build with the standalone CLI:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Build with Artisan:

```bash
php artisan docs:build
```

The default output path is:

```text
build/docs
```

## What To Commit

Commit your source Markdown files, configuration, and any custom theme assets.

Generated `build/docs` output is usually not committed unless your deployment strategy requires committed static files.

---
title: Getting Started
order: 1
---

# Getting Started

Use this section to choose the right Inkstone workflow and build your first static documentation site.

## Choose A Workflow

Use the standalone CLI when your project does not have a Laravel application:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Use Artisan inside a Laravel application:

```bash
php artisan docs:install
php artisan docs:build
```

Both workflows produce the same static output.

## Typical First Build

Create a `docs` directory:

```text
docs/
  README.md
  installation.md
  configuration.md
```

Then run a build:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Open or serve `build/docs` to preview the generated site.

## Next Pages

- [Installation](/getting-started/installation)
- [Standalone Usage](/getting-started/standalone-usage)
- [Laravel Usage](/getting-started/laravel-usage)
- [Package Author Workflow](/getting-started/package-author-workflow)

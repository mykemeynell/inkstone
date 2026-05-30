---
title: Standalone Usage
order: 2
---

# Standalone Usage

The standalone CLI lets Inkstone generate documentation for a package or repository without a full Laravel application.

This workflow is recommended for:

- Laravel packages
- Composer packages
- open-source libraries
- internal packages
- Inkstone's own documentation

## Build A Package Docs Site

From the package root, run:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

This command:

- discovers Markdown files in `docs`
- renders Markdown to HTML
- applies transformers
- builds navigation
- copies theme assets
- writes pages under `build/docs`
- writes `search-index.json`
- writes `sitemap.xml` and `robots.txt` when enabled

## Generate Docs For Another Package

Pass absolute paths when generating documentation for another repository:

```bash
vendor/bin/inkstone docs:build \
  --source=/path/to/package/docs \
  --output=/path/to/package/build/docs
```

Relative paths are resolved from the current working directory.

## Use A Standalone Config File

Create `inkstone.php` in the package root:

```php
<?php

return [
    'source_path' => __DIR__.'/docs',
    'output_path' => __DIR__.'/build/docs',
    'site' => [
        'title' => 'Package Documentation',
        'description' => 'Documentation for my package.',
        'base_url' => '',
    ],
    'github' => [
        'repository' => 'https://github.com/vendor/package',
        'branch' => 'main',
    ],
];
```

Then build with:

```bash
vendor/bin/inkstone docs:build --config=inkstone.php
```

Command-line options are applied after the config file, so this works:

```bash
vendor/bin/inkstone docs:build --config=inkstone.php --output=/tmp/docs-preview
```

## Preview Locally

Use `docs:serve`:

```bash
vendor/bin/inkstone docs:serve --source=docs --output=build/docs --host=127.0.0.1 --port=8080
```

If the output directory does not exist, Inkstone builds first. Pass `--watch` to force a rebuild before serving.

## Clean Output

Remove generated output with:

```bash
vendor/bin/inkstone docs:clean --output=build/docs
```

Inkstone refuses to clean unsafe paths that are not build directories.

## Mount Under A Subdirectory

The default base URL is the served root. If the generated site will be served from a subdirectory, pass `--base-url`:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs --base-url=/docs
```

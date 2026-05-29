---
title: Commands
order: 1
---

# Commands

Inkstone exposes the same documentation commands in both workflows.

Inside Laravel:

```bash
php artisan docs:build
```

Outside Laravel:

```bash
vendor/bin/inkstone docs:build
```

## docs:install

Installs starter documentation, configuration, theme assets, deployment examples, and a generated-output `.gitignore`.

```bash
php artisan docs:install
vendor/bin/inkstone docs:install
```

Options:

| Option | Description |
| --- | --- |
| `--source` | Starter documentation target directory |
| `--output` | Static output directory |
| `--base-url` | Generated site base URL when mounted below a subdirectory |
| `--config` | Optional Inkstone config file |
| `--force` | Overwrite existing Inkstone files |

## docs:build

Builds static documentation.

```bash
php artisan docs:build
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Options:

| Option | Description |
| --- | --- |
| `--source` | Markdown source directory |
| `--output` | Static output directory |
| `--base-url` | Generated site base URL when mounted below a subdirectory |
| `--config` | Optional Inkstone config file |

Build output includes pages, copied assets, search index, and optional static metadata files.

## docs:serve

Serves generated documentation locally using PHP's built-in server.

```bash
php artisan docs:serve
vendor/bin/inkstone docs:serve --source=docs --output=build/docs --host=127.0.0.1 --port=8080
```

Options:

| Option | Description |
| --- | --- |
| `--source` | Markdown source directory |
| `--output` | Static output directory |
| `--base-url` | Generated site base URL |
| `--config` | Optional Inkstone config file |
| `--host` | Local server host |
| `--port` | Local server port |
| `--watch` | Rebuild before serving |

`docs:serve` is for local preview only.

## docs:clean

Removes generated output.

```bash
php artisan docs:clean
vendor/bin/inkstone docs:clean --output=build/docs
```

Options:

| Option | Description |
| --- | --- |
| `--output` | Static output directory |
| `--config` | Optional Inkstone config file |

Inkstone refuses to clean paths that do not look like generated build directories.

## docs:ai-prompt

Generates a Markdown prompt for AI-assisted project documentation.

```bash
php artisan docs:ai-prompt --source=. --write=inkstone/ai/documentation-guide.md
vendor/bin/inkstone docs:ai-prompt --source=. --write=inkstone/ai/documentation-guide.md
```

Options:

| Option | Description |
| --- | --- |
| `--source` | Project source directory to describe |
| `--write` | Optional markdown file to write instead of stdout |
| `--max-files` | Maximum project files to list |

The command does not call an AI provider. It creates a prompt that can be given to an AI coding assistant.

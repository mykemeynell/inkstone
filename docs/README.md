---
title: Introduction
order: 0
---

# Inkstone

Inkstone turns Markdown into a polished static documentation site for Laravel packages, Composer packages, and Laravel applications.

It is designed for README-first projects. You can point it at an existing `docs` directory, run a single build command, and deploy the generated `build/docs` directory to any static host.

## What Inkstone Builds

Inkstone builds a complete static site from Markdown:

```text
Markdown files
    -> document discovery
    -> Markdown parsing
    -> transformer pipeline
    -> navigation builder
    -> Blade theme renderer
    -> static output
```

The generated site includes HTML pages, theme assets, a client-side search index, optional sitemap and robots files, and any configured static assets.

## Where It Runs

Inkstone supports two workflows:

| Workflow | Command | Best for |
| --- | --- | --- |
| Standalone CLI | `vendor/bin/inkstone docs:build` | Packages and repositories without a Laravel app |
| Laravel Artisan | `php artisan docs:build` | Full Laravel applications |

Both workflows use the same package services. The standalone CLI boots a small Illuminate console kernel, so you do not need a full Laravel installation to generate documentation.

## Core Features

- Recursive Markdown and `README.md` discovery
- GitHub Flavored Markdown rendering through `league/commonmark`
- YAML frontmatter for page titles and ordering
- Automatic grouped sidebar navigation
- Sticky "On this page" navigation
- Heading anchors with copyable heading URLs
- GitHub relative link and image rewriting
- Phiki-powered syntax highlighting
- Copy buttons for code and demo source blocks
- Static build-time demo blocks
- Client-side search with typo-tolerant ranking
- AI documentation prompt generation
- Optional Laravel Boost guidelines and documentation skill
- Responsive Blade theme with dark, light, and system modes
- Multiple shipped CSS theme variants
- Source-level `favicon.*` and `logo.*` discovery
- Pretty static URLs under `build/docs`

## Quick Start

Install Inkstone as a development dependency:

```bash
composer require mykemeynell/inkstone --dev
```

For a package or source repository:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

For a Laravel application:

```bash
php artisan docs:install
php artisan docs:build
```

## Documentation Map

- [Installation](/getting-started/installation) explains requirements and first commands.
- [Standalone Usage](/getting-started/standalone-usage) covers package repositories without Laravel.
- [Laravel Usage](/getting-started/laravel-usage) covers Artisan commands and publishing.
- [Configuration](/configuration) lists the main configuration sections.
- [Markdown Rendering](/features/markdown) shows supported Markdown and demo blocks.
- [Themes](/features/themes) covers built-in styling, overrides, and theme variants.
- [AI Assisted Docs](/features/ai-assisted-docs) explains the reusable prompt workflow.
- [Laravel Boost Integration](/features/laravel-boost) explains the optional Boost resources for Laravel apps.
- [Commands](/reference/commands) lists every command and option.
- [Extension Points](/reference/extension-points) documents contracts, DTOs, and replaceable services.

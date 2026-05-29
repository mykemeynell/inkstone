---
title: Laravel Boost Integration
order: 6
---

# Laravel Boost Integration

Inkstone ships optional Laravel Boost resources for Laravel applications that use Boost with an AI coding agent.

The integration is passive. Inkstone does not require `laravel/boost`, and standalone package builds do not need Boost installed.

## What Is Included

Inkstone includes:

```text
resources/boost/guidelines/core.blade.php
resources/boost/skills/inkstone-documentation/SKILL.md
```

The guideline file gives Boost always-available Inkstone context, including command usage, configuration conventions, navigation ordering, static output rules, and non-goals.

The skill gives an AI agent a focused workflow for creating and maintaining Inkstone documentation.

## Use Inside Laravel

Install Boost in the Laravel application that also installs Inkstone:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

When Boost installs or updates its resources, it can discover package-provided guidelines and skills from installed packages.

After updating Inkstone, refresh Boost resources:

```bash
php artisan boost:update
```

## Agent Guidance

The Boost resources tell agents to:

- use `php artisan docs:*` inside Laravel applications
- use `vendor/bin/inkstone docs:*` for standalone package repositories
- keep `config/inkstone.php` and the `inkstone` config key as the Laravel integration point
- use `docs/README.md` as the introduction page
- use folder `index.md` frontmatter to order navigation groups
- keep demo blocks static and build-time rendered
- avoid documenting unsupported dynamic product features

## Outside Laravel

Boost is Laravel-specific. For packages or repositories without a Laravel application, use Inkstone's standalone CLI and the AI prompt command instead:

```bash
vendor/bin/inkstone docs:ai-prompt --source=. --write=inkstone/ai/documentation-guide.md
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

That workflow gives an AI assistant project-aware documentation instructions without requiring a Laravel app.

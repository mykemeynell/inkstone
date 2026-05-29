---
name: inkstone-documentation
description: Create and maintain Inkstone static documentation sites for Laravel apps, Laravel packages, and standalone Composer packages.
---

# Inkstone Documentation

## When To Use This Skill

Use this skill when creating, reviewing, or updating documentation that will be built with Inkstone.

## Workflow

1. Inspect the project structure, public APIs, configuration files, commands, routes, examples, and tests before writing documentation.
2. Build a README-first documentation structure under `docs/`.
3. Use `docs/README.md` as the introduction page.
4. Add frontmatter to every navigation page that needs a stable title or order.
5. Use folder `index.md` files to control group labels and group ordering.
6. Keep examples accurate to the repository. Do not document unsupported features.
7. Build the documentation and fix broken links, invalid demos, or navigation ordering issues.

## Recommended Structure

```text
docs/
  README.md
  getting-started/
    index.md
    installation.md
  configuration/
    index.md
  features/
    index.md
  reference/
    index.md
```

## Page Frontmatter

```yaml
---
title: Installation
order: 1
---
```

Use `title` for the sidebar label. Use `order` for ordering inside the current group only.

For a navigation group, place frontmatter in that group's `index.md`:

```yaml
---
title: Getting Started
order: 2
---
```

## Commands

Inside Laravel:

```bash
php artisan docs:install
php artisan docs:build
php artisan docs:serve
php artisan docs:clean
```

Outside Laravel:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
vendor/bin/inkstone docs:ai-prompt --source=. --write=inkstone/ai/documentation-guide.md
```

## Writing Rules

- Keep the generated site static and deployable.
- Use Markdown links that resolve in the generated site.
- Use `demo:php`, `demo:markdown`, and `demo:html` only for build-time rendered examples.
- Mention Laravel Artisan usage for Laravel apps and standalone CLI usage for packages without Laravel.
- Avoid inventing SaaS, auth, CMS, analytics, AI search, marketplace, collaboration, or live playground features.
- Prefer concise task-oriented pages over long marketing pages.

## Verification

Run the relevant command before finishing:

```bash
php artisan docs:build
```

or:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

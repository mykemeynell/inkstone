# AI Documentation Prompt For Inkstone

Use this prompt with an AI coding assistant to generate or improve documentation for a PHP, Laravel, or Composer package that will be built with Inkstone.

## Project

Project path: `{{ project_path }}`

## Goal

Create Markdown documentation that can be processed by Inkstone into a static documentation site.

The documentation should be accurate, practical, and organized for developers who need to install, configure, use, extend, and deploy the project.

## Output Rules

- Write Markdown files under a `docs/` directory.
- Use `docs/README.md` as the introduction page.
- Use folder `index.md` files to order navigation groups.
- Use frontmatter on every page:

```markdown
---
title: Page Title
order: 1
---
```

- Keep navigation groups ordered with `order` in each folder's `index.md`.
- Prefer root-relative links like `/getting-started/installation` for links between generated documentation pages when building for the served root.
- Use fenced code blocks with language identifiers.
- Use `demo:markdown`, `demo:html`, or `demo:php` blocks only when static rendered examples are useful.
- Do not invent unsupported features.
- Do not document SaaS, authentication, analytics, AI search, CMS, marketplaces, or live playgrounds unless the project actually contains them.

## Suggested Structure

```text
docs/
  README.md
  getting-started/
    index.md
    installation.md
    quick-start.md
  configuration/
    index.md
  features/
    index.md
  reference/
    index.md
    commands.md
```

## What To Inspect

Inspect source code, configuration files, command classes, tests, README content, examples, and package metadata before writing documentation.

Use the discovered project file map below as a starting point. Expand from there if needed.

## Project File Map

{{ file_map }}

## Deliverable

Create or update the Markdown documentation files directly in the project.

After writing documentation, run:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Then fix any broken Markdown, invalid demo blocks, or missing navigation metadata.

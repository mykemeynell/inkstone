---
title: AI Assisted Docs
order: 5
---

# AI Assisted Docs

Inkstone includes a reusable AI documentation prompt for teams that want an AI coding assistant to draft or improve project documentation.

Inkstone does not call an AI service, store credentials, or send project code anywhere. The helper creates a Markdown prompt that you can give to the AI tool of your choice.

## Generate A Prompt

Use:

```bash
vendor/bin/inkstone docs:ai-prompt --source=. --write=inkstone/ai/documentation-guide.md
```

Inside Laravel:

```bash
php artisan docs:ai-prompt --source=. --write=inkstone/ai/documentation-guide.md
```

The generated prompt includes:

- Inkstone documentation conventions
- suggested `docs/` structure
- frontmatter requirements
- navigation ordering guidance
- a project file map
- build verification instructions

## Print To The Terminal

Omit `--write` to print the prompt:

```bash
vendor/bin/inkstone docs:ai-prompt --source=.
```

## Limit File Map Size

Use `--max-files` to control how many project files are listed:

```bash
vendor/bin/inkstone docs:ai-prompt --source=. --max-files=120
```

## Installed Prompt Stub

`docs:install` also publishes the reusable prompt stub to:

```text
inkstone/ai/documentation-guide.md
```

Use that file directly when you want a general prompt without a generated file map.

## Recommended Workflow

1. Generate the AI prompt.
2. Give the prompt to an AI coding assistant with access to the project.
3. Review the generated Markdown for accuracy.
4. Build with Inkstone.
5. Fix broken links, missing frontmatter, or invalid demo blocks.

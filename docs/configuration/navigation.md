---
title: Navigation
order: 1
---

# Navigation

Inkstone builds sidebar navigation from the Markdown file tree.

Navigation uses:

- folder structure
- `README.md` and `index.md` conventions
- frontmatter `title`
- frontmatter `order`
- first H1 fallback
- filename fallback

## Page Titles

Inkstone resolves titles in this order:

1. frontmatter `title`
2. first H1 heading
3. filename converted to title case

```markdown
---
title: Installation
order: 1
---

# Install Inkstone
```

The sidebar label is `Installation`.

## Page Order

`order` sorts pages inside their current navigation group:

```markdown
---
title: Laravel Usage
order: 3
---
```

If two pages have the same order, Inkstone falls back to alphabetical sorting.

## Group Order

Use an `index.md` or `README.md` file inside the folder to control the group itself.

For example:

```text
docs/
  README.md
  getting-started/
    index.md
    installation.md
    standalone-usage.md
```

Set the root page first:

```markdown
---
title: Introduction
order: 0
---
```

Then set the group order in `docs/getting-started/index.md`:

```markdown
---
title: Getting Started
order: 1
---
```

This places `Getting Started` after `Introduction`.

## Parent Groups

Folder parents are labels, not links. When a group has an `index.md` document, Inkstone adds that page as the first `Overview` child.

Example output:

```text
Introduction
Getting Started
  Overview
  Installation
  Standalone Usage
```

This keeps parent labels stable while still exposing the folder index page.

## URL Rules

```text
docs/README.md              -> /
docs/getting-started.md     -> /getting-started
docs/configuration/index.md -> /configuration
docs/configuration/github.md -> /configuration/github
```

When pretty URLs are enabled, these are written as `index.html` files in matching directories.

## Collapsible Sections

Sidebar parent sections can be collapsed. The initial state is configured with `navigation.expanded`:

```php
'navigation' => [
    'expanded' => [], // default: only the active section
],
```

| Value | Behavior |
|---|---|
| `true` | All sections start expanded |
| `false` | All sections start collapsed (active section still expands) |
| `[]` | Only the active section starts expanded |
| `['getting-started', 'features']` | Only the listed slugs start expanded |

The active section always remains expanded regardless of configuration. Collapse state is persisted in the browser across page loads.

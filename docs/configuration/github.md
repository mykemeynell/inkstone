---
title: GitHub Rewriting
order: 2
---

# GitHub Rewriting

Inkstone can rewrite relative Markdown links and images to GitHub raw URLs. This is useful for README-first documentation that already links to repository files.

## Configuration

```php
'github' => [
    'repository' => 'https://github.com/vendor/package',
    'branch' => 'main',
    'rewrite_relative_links' => true,
    'rewrite_images' => true,
],
```

## Relative Links

Markdown like this:

```markdown
[License](LICENSE.md)
```

is rewritten to:

```text
https://raw.githubusercontent.com/vendor/package/main/LICENSE.md
```

## Nested Documents

Links are resolved relative to the current Markdown file.

If this document exists:

```text
docs/getting-started/installation.md
```

and contains:

```markdown
[Logo](../assets/logo.png)
```

Inkstone resolves the path from `docs/getting-started` before creating the GitHub URL.

## Images

Relative image sources are rewritten when `rewrite_images` is enabled:

```markdown
![Screenshot](assets/screenshot.png)
```

This lets existing README image paths continue to work when the docs are generated as a static site.

## Internal Documentation Links

Use root-relative generated URLs for links between documentation pages:

```markdown
[Configuration](/configuration)
```

Root-relative links are treated as site links, not GitHub repository files.

## Disable Rewriting

Disable rewriting when your docs mostly link to generated pages:

```php
'github' => [
    'rewrite_relative_links' => false,
    'rewrite_images' => false,
],
```

---
title: Images
order: 6
---

# Images

Inkstone supports standard Markdown image syntax. Images can be embedded from external URLs, from source-level asset files, or from GitHub raw URLs when rewriting is enabled.

## External Images

Standard Markdown renders images from any URL:

![Inkstone Social Card](https://repository-images.githubusercontent.com/1253711217/bb071f4f-1c5e-4e2a-a27b-2ec710cf4315)

External images are served directly from their source. They are not copied during the build.

## Source-Level Image Assets

Place image files in your documentation source directory and reference them with relative paths:

```text
docs/
├── README.md
├── assets/
│   ├── screenshot.png
│   └── diagram.svg
```

Reference the image in Markdown:

```markdown
![Screenshot](assets/screenshot.png)
```

Inkstone copies image files from the source directory into the generated output.

## GitHub Image Rewriting

When `github.rewrite_images` is enabled, relative image paths are rewritten to raw GitHub URLs:

```markdown
![Screenshot](assets/screenshot.png)
```

This lets existing README images continue to work when the docs are deployed as a static site. The rewrite resolves the path relative to the document location on the configured GitHub repository and branch.

## Image URLs

A source-level image written as `assets/screenshot.png` is resolved in this order:

1. If GitHub rewriting is enabled, it becomes a raw GitHub content URL
2. If GitHub rewriting is disabled, Inkstone copies the file into the output and generates a static asset URL

Image copying is automatic and does not require configuration.

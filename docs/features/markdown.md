---
title: Markdown Rendering
order: 1
---

# Markdown Rendering

Inkstone uses `league/commonmark` to render Markdown into HTML.

The parser supports documentation-style Markdown, including GitHub Flavored Markdown features.

## Supported Markdown

Inkstone supports:

- headings
- paragraphs
- links
- images
- tables
- task lists
- block quotes
- fenced code blocks
- autolinks
- strikethrough
- footnotes
- raw HTML when `html_input` is set to `allow`

## Frontmatter

Frontmatter is extracted before Markdown rendering:

```demo:markdown
---
title: Installation
order: 1
---

# Installation
```

Metadata is stored on the `Document` DTO and used by navigation, rendering, and search.

## Tables

```demo:markdown
| Feature | Status |
| --- | --- |
| Markdown | Supported |
| Search | Supported |
| Static output | Supported |
```

## Task Lists

```demo:markdown
- [x] Install package
- [x] Build docs
- [ ] Deploy output
```

## Footnotes

```demo:markdown
Inkstone supports footnotes in Markdown.[^note]

[^note]: Footnotes are rendered by CommonMark.
```

## Heading Anchors

Headings receive stable IDs and a hover copy button:

```demo:html
<h2 id="installation">Installation</h2>
```

The generated theme also builds an "On this page" sidebar from headings below H1.

## Code Blocks

Code blocks are highlighted with Phiki during the static build.

### Filenames

Specify a filename using `filename`, `file`, or `title`:

````markdown
```php filename="config/inkstone.php"
return [
    'docs_path' => 'docs',
];
```
````

Rendered result:

```php filename="config/inkstone.php"
return [
    'docs_path' => 'docs',
];
```

### Line Highlighting

Highlight lines with curly braces:

````demo:markdown
```php {1,3-4}
// Line 1 is highlighted
// Line 2 is not
// Line 3 is highlighted
// Line 4 is highlighted
```
````

Or use the `highlight` attribute:

````demo:markdown
```php highlight="1,3-4"
// Same result as above
```
````

## Copy Buttons

Code blocks include a copy button by default. Disable it with:

```php
'syntax_highlighting' => [
    'copy_button' => false,
],
```

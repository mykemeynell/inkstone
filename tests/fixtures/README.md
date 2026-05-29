---
title: Laravel Docs Generator
description: Main project overview
order: 1
---

# Laravel Docs Generator

Welcome to the **Laravel Docs Generator** fixture project.

## Features

- Markdown rendering
- GitHub relative links
- Syntax highlighting
- Versioned documentation
- Search indexing

## Relative Links

- [Installation](docs/getting-started/installation.md)
- [Configuration](docs/configuration/index.md)
- [Nested Page](docs/guides/advanced/nested-page.md)

## External Links

- [Laravel](https://laravel.com)
- [GitHub](https://github.com)

## Images

![Logo](assets/logo.png)

## Code Example

```php
<?php

declare(strict_types=1);

namespace App\Services;

class DocsGenerator
{
    public function build(): void
    {
        echo "Building documentation...";
    }
}
```

## Table Example

| Feature | Supported |
|---------|------------|
| Search | Yes |
| Dark Mode | Yes |
| AI Search | No |

## Task List

- [x] Markdown parsing
- [x] Navigation generation
- [ ] AI integration

## Footnote

Documentation generation is important.[^1]

[^1]: Especially for open source packages.

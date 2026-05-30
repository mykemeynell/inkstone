---
title: Package Author Workflow
order: 4
---

# Package Author Workflow

Use this workflow when a package repository wants to replace README-only documentation with a static Inkstone site.

## 1. Install Inkstone

```bash
composer require mykemeynell/inkstone --dev
```

Inkstone can run without a Laravel application:

```bash
vendor/bin/inkstone docs:install --source=docs --output=build/docs
```

For a Laravel package testbench application, the Artisan command is also available:

```bash
php artisan docs:install
```

## 2. Move Existing Docs

Keep your existing README as the entry point:

```text
docs/
  README.md
  installation.md
  configuration.md
```

Inkstone treats `README.md` and `index.md` as section index pages and generates pretty URLs by default.

## 3. Add Frontmatter Where Order Matters

```yaml
---
title: Installation
order: 2
---
```

Use `order` for important pages. Pages without an order fall back to alphabetical ordering.

## 4. Configure GitHub Rewriting

Set the repository and branch when your Markdown links to source files:

```bash
DOCS_GITHUB_REPOSITORY=https://github.com/vendor/package
DOCS_GITHUB_BRANCH=main
```

Relative links such as `LICENSE.md` and images such as `assets/screenshot.png` are rewritten to GitHub raw URLs during the static build.

## 5. Build And Preview

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs
vendor/bin/inkstone docs:serve --source=docs --output=build/docs --watch
```

The generated `build/docs` directory is static. Commit your source Markdown and configuration, not the generated output.

## 6. Deploy

Publish `build/docs` to any static host. GitHub Pages, Netlify, Cloudflare Pages, and Vercel examples are documented in [Static Hosting](/deployment/static-hosting).

For GitHub Pages under a repository path, pass the base URL:

```bash
vendor/bin/inkstone docs:build --source=docs --output=build/docs --base-url=/package-name
```

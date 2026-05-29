## Inkstone

Inkstone generates static documentation sites from Markdown for Laravel applications, Laravel packages, and standalone Composer packages.

### Primary Workflows

Use Artisan commands inside a Laravel application:

<code-snippet name="Build Inkstone docs inside Laravel" lang="shell">
php artisan docs:install
php artisan docs:build
php artisan docs:serve
php artisan docs:clean
</code-snippet>

Use the standalone Composer binary in package repositories or projects that do not have a full Laravel install:

<code-snippet name="Build Inkstone docs without a Laravel app" lang="shell">
vendor/bin/inkstone docs:build --source=docs --output=build/docs
</code-snippet>

Both workflows use the same configuration conventions. Laravel apps read `config/inkstone.php` through the `inkstone` config key. Standalone builds can pass command options such as `--source`, `--output`, `--base-url`, and `--config`.

### Documentation Structure

- Put source Markdown in `docs/` by default.
- Use `docs/README.md` for the introduction page.
- Use a folder `index.md` to control a navigation group title and order.
- Add YAML frontmatter to pages that need explicit navigation labels or ordering:

<code-snippet name="Inkstone page frontmatter" lang="yaml">
---
title: Installation
order: 1
---
</code-snippet>

- Treat `order` as local to the current navigation group.
- Prefer root-relative documentation links such as `/getting-started/installation` when building for the served root.

### Features And Constraints

- Inkstone renders static HTML into `build/docs` by default.
- Search is client-side and generated as `search-index.json`.
- Theme customizations should use the published Blade views, CSS variables, and theme assets.
- Demo fences such as `demo:php`, `demo:markdown`, and `demo:html` are rendered at build time only.
- Do not add SaaS features, authentication, database-backed app state, AI search, CMS features, marketplaces, or live playgrounds unless the host project already implements them.

### Verification

After changing documentation, run the relevant build command and fix broken links, missing frontmatter, or invalid demo blocks before considering the work complete.

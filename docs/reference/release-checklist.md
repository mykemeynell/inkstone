---
title: Release Checklist
order: 5
---

# Release Checklist

Use this checklist before tagging an Inkstone release or switching a package repository to Inkstone-generated docs.

## Build Acceptance

- `composer install` completes from a fresh checkout.
- `npm install` and `npm run build` produce versioned assets.
- `vendor/bin/inkstone docs:install --source=docs --output=build/docs` creates starter files without overwriting existing docs unless `--force` is used.
- `vendor/bin/inkstone docs:build --source=docs --output=build/docs` produces static output.
- `vendor/bin/inkstone docs:serve --source=docs --output=build/docs --watch` serves the generated site locally.

## Static Output

- `build/docs/index.html` exists.
- Nested pages are written as `section/page/index.html`.
- CSS and JavaScript asset URLs resolve from the configured base URL.
- `search-index.json` or the configured search-driver index path exists.
- `sitemap.xml` and `robots.txt` are generated when enabled.
- `sitemap.xml` parses as UTF-8 XML in the Sitemap namespace and contains the canonical deployed document URLs.
- A Laravel-hosted documentation sitemap URL resolves through `app(SitemapExtension::class)->url()` and is served as `application/xml`.
- Any parent sitemap index places the documentation URL in a `<sitemap>` entry and keeps same-site hosting requirements in mind.

## Extensions

- Configured extensions are container-resolved and run once in declaration order after core output.
- Custom extension artifacts are present and valid in the configured output path.
- Extension failures fail the documentation build through the normal command error handling.

## Documentation Behavior

- GitHub relative links and images resolve against the configured repository and branch.
- Sidebar order follows frontmatter `order`, then alphabetical fallback.
- Dark mode persists after reload.
- Search opens from the header icon and Cmd/Ctrl+K shortcut.
- Demo blocks are rendered at build time and are not live playgrounds.

## Quality Gates

```bash
composer validate --strict
composer test
composer test:browser
composer lint
composer analyze
npm run build
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Browser tests require `DUSK_DRIVER_URL`. Without a configured Dusk driver, the browser suite is expected to skip.

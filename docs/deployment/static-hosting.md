---
title: Static Hosting
order: 1
---

# Static Hosting

Inkstone writes deployable static documentation output.

The default output directory is:

```text
build/docs
```

## Output Requirements

Your host needs to serve:

- HTML files
- CSS files
- JavaScript files
- images and copied assets
- `search-index.json`
- optional `sitemap.xml`
- optional `robots.txt`

No production PHP process is required.

## GitHub Pages

Use a GitHub Actions workflow that uploads `build/docs` as the Pages artifact.

Inkstone ships a starter workflow at:

```text
stubs/deploy/github-pages.yml
```

For standalone package repositories, the core steps are:

```yaml
- uses: actions/checkout@v4
- uses: shivammathur/setup-php@v2
  with:
    php-version: '8.3'
- run: composer install --prefer-dist --no-progress
- run: vendor/bin/inkstone docs:build --source=docs --output=build/docs
- uses: actions/upload-pages-artifact@v3
  with:
    path: build/docs
```

If GitHub Pages serves the site below a repository path such as `/my-package`, set `INKSTONE_BASE_URL=/my-package` or pass `--base-url=/my-package` during the build.

## Netlify

Use:

```text
Build command: composer install && vendor/bin/inkstone docs:build --source=docs --output=build/docs
Publish directory: build/docs
```

Laravel applications can use:

```text
Build command: composer install && php artisan docs:build
Publish directory: build/docs
```

## Cloudflare Pages

Use:

```text
Build command: composer install && vendor/bin/inkstone docs:build --source=docs --output=build/docs
Output directory: build/docs
```

## Vercel

Use:

```text
Build command: composer install && vendor/bin/inkstone docs:build --source=docs --output=build/docs
Output directory: build/docs
```

## Pretty URLs

With pretty URLs enabled, Inkstone writes:

```text
build/docs/index.html
build/docs/getting-started/installation/index.html
build/docs/configuration/github/index.html
```

Most static hosts serve these paths from the published directory root as:

```text
/
/getting-started/installation
/configuration/github
```

## Base URL

Set `site.base_url` or pass `--base-url` to match the deploy path:

```bash
vendor/bin/inkstone docs:build --base-url=/docs
```

For a root-domain documentation site or any host that serves `build/docs` as the web root, omit `--base-url` or use:

```bash
vendor/bin/inkstone docs:build --base-url=/
```

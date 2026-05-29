---
title: Deployment
order: 4
---

# Deployment

Inkstone output is static. Deploy the generated `build/docs` directory to any host that can serve static files.

Common deployment targets include:

- GitHub Pages
- Cloudflare Pages
- Netlify
- Vercel
- S3-compatible object storage
- any static file server

The generated output does not require PHP in production.

## Default Output

```text
build/docs
```

## Build Before Deploy

Standalone packages usually use:

```bash
composer install --no-interaction --prefer-dist
vendor/bin/inkstone docs:build --source=docs --output=build/docs
```

Laravel applications usually use:

```bash
composer install --no-interaction --prefer-dist
php artisan docs:build
```

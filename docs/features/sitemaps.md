---
title: Sitemaps
order: 8
---

# Sitemaps

Inkstone generates `sitemap.xml` through its bundled `SitemapExtension`. The extension is enabled by default and runs after pages, search, robots metadata, and assets have been written.

The sitemap includes processed Markdown and OpenAPI document URLs. It preserves pretty or `.html` URLs from the build, removes duplicate locations, escapes location values, and writes namespace-correct UTF-8 XML.

## Configure A Canonical URL

Search engines require fully qualified sitemap locations. For a static deployment, set an absolute `site.base_url` that includes any deployment path:

```php
'site' => [
    'base_url' => 'https://docs.example.com/product',
],
```

Use a canonical base without a query string or fragment. Inkstone rejects malformed HTTP URLs rather than writing protocol-invalid sitemap locations.

In a Laravel application, Inkstone resolves the sitemap base in this order:

1. An absolute `site.base_url` is used as configured.
2. A relative `site.base_url`, such as `/docs`, is resolved against the Laravel application URL.
3. With no site base, Inkstone uses its named documentation route, including the configured route path and fixed domain.

When Inkstone cannot obtain an origin, including in a standalone build without an absolute site base, it retains relative locations for backwards compatibility and emits one non-fatal warning. Production builds should configure an absolute `site.base_url` before submitting that sitemap to a search engine.

## Generated Output

The default build writes:

```text
build/docs/sitemap.xml
```

Disable the bundled sitemap while keeping other extensions enabled with the existing setting:

```php
'build' => [
    'generate_sitemap' => false,
],
```

Inkstone emits one sitemap file. A build fails clearly instead of writing an invalid sitemap if it would exceed 50,000 URLs or 50 MB uncompressed, as defined by the [Sitemaps protocol](https://www.sitemaps.org/protocol.html) and [Google Search guidance](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap).

## Include It In A Parent Sitemap

When Laravel serves the generated documentation, resolve the extension to obtain its public URL:

```php
use Inkstone\Extensions\SitemapExtension;

$documentationSitemapUrl = app(SitemapExtension::class)->url();
```

Include that URL in the parent application's sitemap index:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.test/docs/sitemap.xml</loc>
    </sitemap>
</sitemapindex>
```

A `<sitemap>` entry belongs in `<sitemapindex>`, not in the `<urlset>` used for individual pages. Sitemap indexes normally list sitemaps on the same site. When documentation is hosted on another domain, use a sitemap index on that host or follow the search engine's verified cross-site submission process.

Inkstone's existing wildcard route serves the generated XML as `application/xml`; no separate sitemap route or third-party package is required.

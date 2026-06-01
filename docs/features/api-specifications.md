---
title: API Specifications
order: 7
---

# API Specifications

Inkstone can auto-discover OpenAPI specification files in your documentation source directory and render them as browsable API reference pages alongside your Markdown guides.

Place an `openapi.yaml`, `openapi.yml`, or `openapi.json` file in your `docs/` directory and Inkstone will generate a structured API reference page with endpoints organized by tag, parameter tables, request/response schema documentation, and server information.

## How It Works

The OpenAPI spec file is treated as a documentation source alongside Markdown files. Inkstone:

1. Discovers the spec file during the build
2. Parses it using `cebe/php-openapi` (supports OpenAPI 3.0, 3.1, and Swagger 2.0)
3. Converts each endpoint into a rich HTML section with method badges, parameter details, and schema tables
4. Generates an auto-updating table of contents with endpoint groups
5. Renders everything through the same Blade theme as your Markdown pages

No separate workflow or configuration is required for basic usage. The spec file *is* the API reference.

## What Gets Rendered

For each spec file, Inkstone generates:

- **Info header**: API title, version, and description
- **Servers table**: Base URLs with descriptions
- **Endpoints by tag**: Each tag becomes a section with collapsible endpoint cards:
  - Method badge (GET, POST, PUT, PATCH, DELETE) with color coding
  - Full endpoint path and summary
  - Description text
  - Parameters table with name, location, type, required status, and description
  - Request body schema table (when applicable)
  - Response status codes with schema details
- **Schemas section**: All component schemas rendered as structured field tables

## Supported Spec Formats

Inkstone discovers spec files by configured filenames in your documentation source directory:

| Format | Default Filename |
| --- | --- |
| OpenAPI 3.0 YAML | `openapi.yaml`, `openapi.yml` |
| OpenAPI 3.0 JSON | `openapi.json` |
| OpenAPI 3.1 YAML | `openapi.yaml`, `openapi.yml` |
| OpenAPI 3.1 JSON | `openapi.json` |
| Swagger 2.0 YAML | `openapi.yaml`, `openapi.yml` |
| Swagger 2.0 JSON | `openapi.json` |

Reference resolution is handled automatically — `$ref` pointers within the spec are resolved inline during parsing.

## Example

Given this `docs/openapi.yaml`:

```yaml
openapi: 3.0.0
info:
  title: Users API
  version: 1.0.0
  description: A simple API for managing users.
servers:
  - url: https://api.example.com/v1
tags:
  - name: Users
paths:
  /users:
    get:
      summary: List all users
      tags: [Users]
      parameters:
        - name: page
          in: query
          schema:
            type: integer
      responses:
        '200':
          description: A paginated list of users
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/User'
  /users/{id}:
    get:
      summary: Get a user by ID
      tags: [Users]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: A single user
        '404':
          description: User not found
components:
  schemas:
    User:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
        email:
          type: string
```

Inkstone renders this as an API reference page with:

- "Users API" page title with version badge
- Server URL listed in a table
- "Users" tag section with all endpoints grouped under it
- `GET /users` endpoint with query parameter table and response schema
- `GET /users/{id}` endpoint with path parameter and response codes
- "User" schema table with field names, types, and descriptions

## Integrating With Markdown Guides

API spec files live alongside your Markdown files in the same source directory. You can link between them naturally:

```markdown
See the [API Reference](/api/openapi) for endpoint details.
```

The API page appears in the sidebar navigation automatically, grouped under an "API" section. The search index includes all endpoint descriptions, parameter details, and schema documentation — so readers can find API content directly from search.

## CLI Options

```bash
# Include a spec file from a custom location
vendor/bin/inkstone docs:build --api-spec=resources/api-spec/openapi.yaml

# Disable API documentation for a specific build
vendor/bin/inkstone docs:build --no-api
```

## Configuration

```php
'api' => [
    'enabled' => true,
    'spec_filenames' => [
        'openapi.yaml',
        'openapi.yml',
        'openapi.json',
    ],
    'base_path' => 'api',
    'generate_code_examples' => true,
],
```

| Key | Default | Description |
| --- | --- | --- |
| `enabled` | `true` | Enable or disable API spec discovery |
| `spec_filenames` | `['openapi.yaml', 'openapi.yml', 'openapi.json']` | Filenames to search for in the source directory |
| `base_path` | `'api'` | URL path prefix for generated API pages |
| `generate_code_examples` | `true` | Auto-generate request code examples from schemas |

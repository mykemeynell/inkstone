---
title: Themes
order: 2
---

# Themes

Inkstone renders documentation with Blade views and package CSS/JavaScript assets.

The default theme is responsive, supports dark mode, includes client-side search, adds copy controls, and renders sticky sidebar navigation.

## Built-In Theme Structure

Theme assets are split into two parts:

```text
resources/css/inkstone.css
resources/css/themes/default.css
resources/css/themes/light.css
resources/css/themes/dark.css
resources/css/themes/ember.css
resources/css/themes/forest.css
resources/js/inkstone.js
resources/views/themes/default/page.blade.php
resources/views/themes/default/partials/navigation-item.blade.php
```

`inkstone.css` contains base layout, component, and interaction styles.

`themes/<theme>.css` contains color, font, shadow, and variable values.

## Available Theme Variants

Inkstone ships these CSS theme variants:

| Theme | Purpose |
| --- | --- |
| `default` | Balanced default documentation theme |
| `light` | Bright, airy documentation theme |
| `dark` | Sleek dark-first theme |
| `ember` | Warm accent theme |
| `forest` | Green accent theme |

Set the active variant:

```php
'theme' => [
    'name' => 'forest',
    'layout' => 'default',
],
```

`theme.name` chooses the CSS variable file. `theme.layout` chooses the Blade layout. The included variants all use the default layout unless you publish or provide another layout.

For example, this uses the Ember colors with the default documentation layout:

```php
'theme' => [
    'name' => 'ember',
    'layout' => 'default',
],
```

## Dark Mode

The default theme supports:

- system mode
- light mode
- dark mode
- local storage persistence
- sun, moon, and system icons

Configure the default mode:

```php
'theme' => [
    'default_mode' => 'system',
],
```

Valid values are `system`, `light`, and `dark`.

## Publishing Views

Inside Laravel, publish theme views:

```bash
php artisan vendor:publish --tag=inkstone-theme
```

Published views are written to:

```text
resources/views/vendor/inkstone/themes/default
```

Laravel will load published views before package views.

To add a custom layout, create a matching layout directory and set `theme.layout`:

```text
resources/views/vendor/inkstone/themes/modern/page.blade.php
```

```php
'theme' => [
    'name' => 'default',
    'layout' => 'modern',
],
```

## Publishing Assets

Publish theme source assets:

```bash
php artisan vendor:publish --tag=inkstone-assets
```

Published assets are written to:

```text
resources/inkstone/css
resources/inkstone/js
```

## Branding

Set explicit logo and favicon URLs:

```php
'site' => [
    'favicon' => '/favicon.svg',
    'logo' => '/assets/logo.svg',
],
```

Or place files in the source docs directory and let Inkstone discover them:

```text
docs/favicon.svg
docs/logo.svg
```

The generator copies discovered files into the static output and updates the rendered theme config for that build.

## Credits Footer

The default theme renders a small credits footer below the page content:

```php
'site' => [
    'footer' => [
        'enabled' => true,
        'text' => 'Built with Inkstone',
        'url' => 'https://github.com/mykemeynell/inkstone',
    ],
],
```

Set `enabled` to `false` to hide it, or change `text` and `url` to point to your own project.

## Theme JavaScript

The default JavaScript handles:

- mobile sidebar toggling
- theme mode switching
- code copy buttons
- heading link copy buttons
- active table-of-contents tracking
- search ranking and highlighting
- back-to-top behavior

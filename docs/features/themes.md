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

## Syntax Highlighting

Inkstone uses **Phiki** for build-time syntax highlighting. This ensures zero runtime overhead and fast page loads.

Configure the syntax highlighter:

```php
use Phiki\Theme\Theme;

'theme' => [
    'syntax_highlighting' => [
        'enabled' => true,
        'theme' => [
            'light' => Theme::GithubLight,
            'dark' => Theme::GithubDark,
        ],
        'show_line_numbers' => true,
        'copy_button' => true,
    ],
],
```

The light and dark themes are used automatically based on the current theme mode.

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

`logo` accepts a string or an array with separate light and dark variants:

```php
'logo' => [
    'light' => '/assets/logo-light.svg',
    'dark' => '/assets/logo-dark.svg',
],
```

When an array is provided, the theme automatically switches between the two variants based on the current color mode.

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

## CSS Custom Properties

Each theme defines color, font, and shadow values as CSS custom properties on `:root` (light mode) and `.dark` (dark mode). These can be overridden in custom themes or per-page styles.

### Typography

| Property | Purpose |
|---|---|
| `--inkstone-font-sans` | Primary sans-serif font stack |
| `--inkstone-font-mono` | Monospace font stack for code |

### Colors

| Property | Purpose |
|---|---|
| `--inkstone-bg` | Page background |
| `--inkstone-panel` | Card, dialog, and panel background |
| `--inkstone-header` | Header bar background |
| `--inkstone-control` | Input and button background |
| `--inkstone-control-hover` | Control hover state |
| `--inkstone-search-bg` | Search input background |
| `--inkstone-text` | Body text |
| `--inkstone-heading` | Heading text |
| `--inkstone-muted` | Secondary/muted text |
| `--inkstone-border` | Default border |
| `--inkstone-border-soft` | Subtle border |
| `--inkstone-border-strong` | Strong border |
| `--inkstone-accent` | Accent / brand color |
| `--inkstone-link` | Link text |
| `--inkstone-focus` | Focus ring (rgba) |
| `--inkstone-wash` | Subtle accent wash (rgba) |
| `--inkstone-nav-active` | Active navigation item background |
| `--inkstone-nav-parent-text` | Collapsible navigation parent label text |
| `--inkstone-code-bg` | Code block background |
| `--inkstone-code-bar` | Code filename bar background |
| `--inkstone-code-text` | Code block text |
| `--inkstone-inline-code` | Inline code background |
| `--inkstone-inline-code-text` | Inline code text |
| `--inkstone-highlight` | Highlighted code line background |
| `--inkstone-quote-bg` | Blockquote background |
| `--inkstone-table-head` | Table header background |
| `--inkstone-demo-output` | Demo output panel background |
| `--inkstone-copy-bg` | Copy button background |
| `--inkstone-mark` | Search result highlight |
| `--inkstone-scrollbar` | Custom scrollbar color |

### Shadows

| Property | Purpose |
|---|---|
| `--inkstone-control-shadow` | Small control shadow |
| `--inkstone-panel-shadow` | Panel / card shadow |
| `--inkstone-code-shadow` | Code block shadow |
| `--inkstone-search-shadow` | Search dialog shadow |
| `--inkstone-glass-shadow` | Glass border effect (multi-layer) |

### Keyboard Shortcuts

| Property | Purpose |
|---|---|
| `--inkstone-kbd-bg` | Keyboard shortcut badge background |
| `--inkstone-kbd-border` | Keyboard shortcut badge border |
| `--inkstone-kbd-text` | Keyboard shortcut badge text |

### Syntax Highlighting (Dark Mode)

These properties control Phiki syntax highlighting in dark mode:

| Property | Purpose |
|---|---|
| `--phiki-dark-background-color` | Code block background in dark mode |
| `--phiki-dark-color` | Code block text color in dark mode |
| `--phiki-dark-font-style` | Token font style override |
| `--phiki-dark-font-weight` | Token font weight override |
| `--phiki-dark-text-decoration` | Token text decoration override |

### Demo Code Blocks

| Property | Purpose |
|---|---|
| `--inkstone-demo-code-bg` | Demo source/output code background (defaults to `--inkstone-code-bg`) |
| `--inkstone-demo-code-border` | Demo source/output code border (defaults to `--inkstone-border`) |

## Theme JavaScript

The default JavaScript handles:

- mobile sidebar toggling
- collapsible navigation sections with localStorage persistence
- theme mode switching with animation
- code copy buttons (subtle appearance)
- heading link copy buttons
- active table-of-contents tracking
- configurable search trigger (input box or icon button)
- global search keyboard shortcut (`⌘K` / `Ctrl+K`)
- search ranking and highlighting
- back-to-top behavior

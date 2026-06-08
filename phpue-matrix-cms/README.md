# PHPue Matrix — Overview

PHPue Matrix is a flat-file CMS extension for the PHPue framework. It stores content as PHP arrays, renders pages through theme closures, and requires zero database queries.

---

## What It Does

PHPue Matrix handles **content rendering** — the body of your pages. It does not manage `<head>` metadata (titles, descriptions, OpenGraph tags). That's handled separately by your existing SEO setup in `App.pvue`.

| Responsibility | Handled By |
|---------------|-------------|
| Content storage | `createPage()` → `.php` files on disk |
| Content rendering | `getPage()` → theme closures → HTML body |
| Theme management | `getTheme()` → section definitions for admin UI |
| Page listing | `listPages()` → scans route folders |
| Route listing | `listRoutes()` → scans all category folders |
| Page deletion | `deletePage()` → removes `.php` file |
| Shortcode parsing | `parseShortcodes()` → `[[token]]` and `[[namespace.key]]` |
| SEO metadata | Your existing setup (`App.pvue`, MetaControl, or manual) |

---

## Core API

### `getPage(string $route, string $slug): string`

Loads a page file, reads its theme, loops through sections, calls render closures, parses shortcodes, and returns the full HTML body.

```php
$html = \PHPueExt\PHPueMatrix::getPage('blogs', 'my-post');
// Returns rendered HTML — drop into any .pvue template
```

### `createPage(string $route, string $slug, string $theme, array $sections, string $title, string $status): bool`

Serializes page data with `var_export()` and writes it to disk as a `.php` file.

```php
$sections = [
    ['type' => 'hero', 'data' => ['heading' => 'Welcome', 'bg_image' => '...']],
    ['type' => 'content', 'data' => ['heading' => 'About', 'body' => '...']],
];

\PHPueExt\PHPueMatrix::createPage('blogs', 'hello-world', 'default', $sections, 'Hello World', 'live');
```

### `getTheme(string $name): array`

Returns theme metadata and section field definitions (without closures) — used by the admin UI to build forms.

### `listPages(string $category): array`

Scans a route folder and returns all pages with slug, title, status, and creation date.

### `listRoutes(): array`

Scans the pages directory for all route folders and returns their names and page counts.

### `deletePage(string $route, string $slug): bool`

Removes a page file from disk.

---

## Theme Structure

Themes are PHP files that return an array with `meta`, `shortcodes`, and `sections`. Each section has a `label`, `fields` array, and `render` closure.

```php
// themes/default.php
return [
    'meta' => ['name' => 'Editorial Blog Theme', 'version' => '2.0'],
    'shortcodes' => [
        'phpueLang' => fn($key) => \PHPueExt\PHPueMatrix::$phpueLang[$key] ?? "[[phpueLang.{$key}]]",
        'year' => fn() => date('Y'),
    ],
    'sections' => [
        'hero' => [
            'label' => 'Hero Banner',
            'fields' => ['heading', 'subheading', 'bg_image'],
            'render' => function(array $data): string {
                // Return Tailwind-styled HTML
            },
        ],
        // ... more sections
    ],
];
```

---

## Shortcodes

Content can include `[[token]]` patterns that are resolved at render time. Dot notation enables wildcard namespaces.

```php
// In theme shortcodes:
'phpueLang' => fn($key) => $GLOBALS['phpueLang'][$key] ?? "[[phpueLang.{$key}]]"

// In page content:
"Welcome to [[phpueLang.site_name]] — est. [[year]]"
```

The shortcode parser runs as the final step in `getPage()`, so it works across all section types.

---

## OpCache Strategy

Every method that reads or writes files includes `opcache_invalidate()` calls:

| Method | Invalidates |
|--------|-------------|
| `getPage()` | Page file + Theme file before reading |
| `createPage()` | Written file after saving |
| `getTheme()` | Theme file before reading |
| `listPages()` | Each page file before including |
| `deletePage()` | File before unlinking |

This ensures fresh data on every request while maintaining sub-1ms OpCache performance on the frontend.

---

## Separation of Concerns

PHPue Matrix outputs **body HTML only**. It does not:

- Output `<head>` metadata
- Manage `<title>` tags
- Set OpenGraph or Twitter Card tags
- Handle canonical URLs

Those are handled by your existing SEO layer — whether that's MetaControl, manual variables in `App.pvue`, or another approach. Matrix just gives you the rendered content to drop into your template. However, it is relativity simple to add to MetaControl in a dynamic sense.

```html
// In your .pvue view:
<script>
    $page = \PHPueExt\PHPueMatrix::getPage('blogs', $_GET['slug'] ?? '');
    // SEO is handled separately by your App.pvue or MetaControl
</script>

<template>
    <?= $page ?>  <!-- Just the body content -->
</template>
```
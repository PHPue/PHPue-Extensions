# MetaControl in PHPue — Full Explanation

MetaControl is a PHPue extension designed to provide dynamic server-side metadata control, even inside header sections where PHP normally cannot be executed. PHPue restricts dynamic PHP execution inside &lt;header&gt; blocks in .pvue files, so MetaControl works as a central metadata store that can be read by the global layout (App.pvue) and by any view or backend helper.

MetaControl makes it possible to define metadata once, anywhere in your app, and use it across the rendering pipeline:

- in views

- in backend classes

- during AJAX requests

- inside the global layout (App.pvue)

This avoids duplication and makes it possible to build SEO-correct pages with both SSR and dynamic updates.

## Core Principles
1. Global Shared Singleton

Every part of PHPue retrieves the same MetaControl instance:

```php
$pageMeta = \PHPueExt\MetaControl::getInstance();
```


This ensures that metadata set in one location is immediately available everywhere else within the same request.

2. Dynamic Metadata Storage

You can store any metadata under a key:

```php
$pageMeta->setMetaVariable('page-title', 'Home Page');
```


But MetaControl is not limited to titles.

**You can store:**

- descriptions

- keywords

- OpenGraph tags

- Twitter Card tags

- robots rules

- canonical URLs

- custom per-page metadata

For example:

```php
$pageMeta->setMetaVariable('description', 'This is the page description.');
$pageMeta->setMetaVariable('keywords', 'php, phpue, framework, seo');
$pageMeta->setMetaVariable('og:title', 'OpenGraph Title Here');
$pageMeta->setMetaVariable('og:description', 'OpenGraph description text.');
$pageMeta->setMetaVariable('canonical', 'https://example.com/page');
```

Anything stored this way can later be retrieved in App.pvue or anywhere else:

```php
$pageMeta->getMetaVariable('description');
$pageMeta->getMetaVariable('og:title');
```

This makes MetaControl a universal metadata manager.

3. Reading Metadata Anywhere

Because MetaControl is globally accessible, any part of the application can read stored values:

```php
$currentTitle = $pageMeta->getMetaVariable('page-title');
$currentDescription = $pageMeta->getMetaVariable('description');
```


This is particularly important in:

your backend SEO helper

- App.pvue (which outputs the header section)

- views

- AJAX controllers

4. Automatic Resetting Between Pages

MetaControl tracks when a page has already rendered.
If the user refreshes or navigates to another route:

```php
if($pageMeta->getRendered()) {
    $pageMeta->unsetMetaVariables();
}
```


This ensures metadata does not “bleed” from one page to another.

How Your Setup Works

backend/seo-helper/seoMetaIndex.php

This file:

- Reads stored metadata from MetaControl.

- Builds a valid &lt;title&gt; tag based on the stored title.

- Falls back to "Initial SSR Title" if no title has been set yet.

- Resets metadata after the page is rendered.

- Although it currently manages only the title, it can be extended to produce additional tags such as:

```php
<meta name="description" content="...">
<meta property="og:title" content="...">
```

Because MetaControl stores any key, this class can easily output a full SEO block.

**App.pvue**

App.pvue is responsible for outputting dynamic &lt;header&gt; HTML.

Since PHP cannot execute directly in the header, App.pvue uses PHPue’s context-aware variable system:

{{$pageTitle}}


App.pvue retrieves its header values from your backend helper.
Anything you store in MetaControl can be retrieved here and included in the header.

You could eventually print multiple metadata values like:

{{$pageTitle}}
{{$metaDescription}}
{{$ogTags}}
{{$twitterTags}}

---

**views/index.pvue**

index.pvue modifies metadata dynamically:

It reads the title via your helper.

It exposes an AJAX function that updates metadata:

```php
$pageMeta->setMetaVariable('page-title', $input['title']);
```

This same method works for any other metadata:

```php
$pageMeta->setMetaVariable('description', $input['desc']);
$pageMeta->setMetaVariable('og:image', $input['ogImage']);
```

After an AJAX update, you simply reload the page, and App.pvue will produce new SSR header output.

---

**Page2.pvue**

This demonstrates static SEO metadata and shows that MetaControl resets between pages to prevent contamination.

## Summary

- MetaControl is a flexible metadata management system for PHPue.
It provides the following capabilities:

- Stores any kind of metadata dynamically.

- Accessible globally through a shared singleton.

- Works with titles, descriptions, keywords, canonical URLs, OpenGraph data, and more.

- Solves PHPue’s restriction on dynamic PHP inside &lt;header&gt; sections.

- Integrates cleanly with backend helpers, SSR, and AJAX.

- Automatically resets metadata when navigating to a new page.

**The key idea is:**

Anything you can store with ``` $pageMeta->setMetaVariable('key', 'value') ``` can be dynamically output in App.pvue’s header. **Note:** Just make sure {{ $containsVar }} (safe) rather than {{ $pageMeta->setMetaVariable('key', 'value') }} (unsafe).
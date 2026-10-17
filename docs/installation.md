# Installation

## Requirements

- Statamic 6.35 or later, PHP 8.3 or later.
- An account with a cookie consent platform: Cookiebot, Usercentrics, OneTrust, CookieYes, iubenda, Termly, CookieFirst, Cookie-Script, or another one through [Custom](platforms.md#custom).

## Installing

```bash
composer require 1994/cookie-connect-statamic
```

## Setting it up

1. Open **Tools → Cookie Connect** in the Control Panel. On a multisite install, each site has its own page (under the nav item, or the links at the top of the page).
2. Choose your platform and paste its ID, or the whole snippet the platform gives you. Cookie Connect takes the ID out of it and checks it looks right.
3. Optionally, add your Google Tag Manager container or Google Analytics 4 measurement ID (see [Google tags](google.md)).
4. Save. The box at the top says whether the platform is live.

You don't need to change your templates: Cookie Connect puts itself straight after `<head>` on every front-end page.

## Where it runs

The platform only loads in the environments you choose, by `APP_ENV`. The default is `production`, so on your local copy the box says "off in this environment". Change it under **Tools → Addons → Cookie Connect → Settings**, or in [config](configuration.md):

```php
'environments' => ['production', 'staging'],   // or '*' for everywhere
```

Where it doesn't run, content that needs consent stays blocked. To see your tags working on staging without a consent platform, choose **Run it as if accepted** under **Elsewhere**.

Some platforms tie their ID to your domain (Cookiebot's domain group, OneTrust's production script, CookieFirst's site). Add your staging domain in the platform, or use its test ID there; see [Choosing your platform](platforms.md).

## Permissions

Super users can always change Cookie Connect. Give a role **Choose the consent platform and edit Cookie Connect's settings for each site** under Cookie Connect in its permissions.

## Where settings are kept

Each site's settings are saved as `resources/cookie-connect/{site}.yaml`, so they're versioned with your content. The addon settings are in `resources/addons/cookie-connect.yaml`, like any addon's.

## Updating

```bash
composer update 1994/cookie-connect-statamic
```

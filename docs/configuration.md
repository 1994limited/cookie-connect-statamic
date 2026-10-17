# Configuration

## The addon settings

**Tools → Addons → Cookie Connect → Settings**:

- **Environments**: where the consent platform loads, as `APP_ENV` values, or `*` for everywhere. Default: `production`.
- **Elsewhere**: what happens to consent-gated content where it doesn't load (and on sites with no platform chosen): keep it blocked (default), run it as if accepted (to test tags), or leave it out.
- **Add to every page by itself**: on by default. Off: put `{{ consent:head }}` straight after `<head>` in your layout, and `{{ consent:body_start }}` / `{{ consent:body_end }}` after `<body>` and before `</body>` if you use body scripts.
- **Debug in the browser console**.

Each site's platform, Google tags and scripts are under **Tools → Cookie Connect**.

## config/cookie-connect.php

```bash
php artisan vendor:publish --tag=cookie-connect-config
```

Anything set there wins over the Control Panel, and shows there as fixed.

```php
return [
    'environments' => ['production', 'staging'],
    'when_off' => env('APP_ENV') === 'local' ? 'run' : 'block',
    'auto_insert' => true,
    'nonce' => null,
    'sites' => [],
    'path' => resource_path('cookie-connect'),
];
```

### Per-site settings in config

`sites` sets a site's platform, values, Google tags or scripts, by site handle, or `*` for every site:

```php
'sites' => [
    '*' => [
        'provider' => 'onetrust',
        'values' => ['domainScript' => env('ONETRUST_ID')],
        'google' => ['gtmId' => env('GTM_ID')],
    ],
    'german' => [
        'categoryMap' => ['analytics' => '2', 'marketing' => '4'],
    ],
],
```

With `ONETRUST_ID` set to the `-test` ID on staging and the production ID live, each environment uses the right one.

The keys: `provider` (`cookiebot`, `usercentrics`, `onetrust`, `cookieyes`, `iubenda`, `termly`, `cookiefirst`, `cookie-script`, `custom`), `values` (the platform's fields, by the handles in [the provider files](https://github.com/1994limited/cookie-connect-core/tree/main/resources/providers)), `categoryMap`, `google` (`gtmId`, `ga4Id`, `consentMode`, `mode`, `regions`, `urlPassthrough`, `adsDataRedaction`, `gtmSetsDefaults`, `waitForUpdate`) and `scripts` (each with `name`, `categories`, `placement`, `code`, `enabled`).

## Multisite

Each site has its own settings and its own page under **Tools → Cookie Connect**. The platform's banner is shown in each site's language where the platform supports it (Cookiebot, Usercentrics, OneTrust).

## Static caching

What Cookie Connect prints depends only on the site's settings, not the visitor: the visitor's choice is read in the browser. So it works with half and full static caching. Cookie Connect adds itself before Statamic caches the page, and saving its settings clears the static cache so pages pick up the change.

## Content Security Policy

If your CSP uses nonces, give Cookie Connect a function that returns this request's nonce. Every script it prints (its own, the platform's, Google's, and the ones it holds back) gets it. With spatie/laravel-csp:

```php
'nonce' => fn () => csp_nonce(),
```

Full static caching and nonces don't mix (a cached page carries an old nonce). Use hashes instead: `{{ consent:hashes }}` gives the `'sha256-…'` sources for the inline scripts Cookie Connect printed, for your policy header. They only change when the settings do.

Your platform's own domains need allowing too; see its CSP documentation.

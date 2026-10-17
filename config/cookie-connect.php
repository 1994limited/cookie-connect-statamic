<?php

/*
 * Cookie Connect. Publish with:
 *
 *     php artisan vendor:publish --tag=cookie-connect-config
 *
 * Anything set here wins over the Control Panel, and shows there as fixed.
 */

return [

    /*
    | Where the consent platform loads: APP_ENV values, or '*' for everywhere.
    | Null uses the addon settings (Tools → Addons → Cookie Connect).
    */
    'environments' => null,

    /*
    | Elsewhere (and on sites with no platform chosen), content that needs
    | consent is kept blocked ('block'), run as if accepted ('run') or left
    | out ('omit'). Null uses the addon settings.
    */
    'when_off' => null,

    /*
    | Add Cookie Connect to every front-end page by itself. Off: put
    | {{ consent:head }} straight after <head> in your layout. Null uses the
    | addon settings.
    */
    'auto_insert' => null,

    /*
    | A function returning this request's CSP nonce, if your site uses one.
    | For example, with spatie/laravel-csp: fn () => csp_nonce()
    */
    'nonce' => null,

    /*
    | Per-site settings, by site handle or '*' for every site. These win over
    | what's saved in the Control Panel. Strings like '$COOKIEBOT_ID' aren't
    | special here: use env('COOKIEBOT_ID').
    |
    | 'sites' => [
    |     '*' => [
    |         'provider' => 'cookiebot',
    |         'values' => ['cbid' => env('COOKIEBOT_ID')],
    |         'google' => ['gtmId' => env('GTM_ID')],
    |     ],
    | ],
    */
    'sites' => [],

    /*
    | Where each site's settings from the Control Panel are kept, one YAML
    | file per site.
    */
    'path' => resource_path('cookie-connect'),

];

<?php

namespace NineteenNinetyFour\CookieConnect\Blueprints;

use NineteenNinetyFour\CookieConnect\Settings;

/**
 * Tools → Addons → Cookie Connect → Settings: where the platform loads and
 * how Cookie Connect gets into pages.
 */
class SettingsBlueprint
{
    public function contents(): array
    {
        $defaults = Settings::defaults();
        $fixed = app(Settings::class)->overridden();
        $note = fn (string $key) => in_array($key, $fixed, true) ? ' '.__('Set in config/cookie-connect.php.') : '';

        return [
            'tabs' => [
                'main' => [
                    'display' => __('Settings'),
                    'sections' => [[
                        'instructions' => __('Each site’s consent platform, Google tags and scripts are under Tools → Cookie Connect.'),
                        'fields' => [
                            ['handle' => 'environments', 'field' => [
                                'type' => 'text',
                                'display' => __('Environments'),
                                'instructions' => __('Where the consent platform loads, as APP_ENV values separated by commas, or * for everywhere. This environment is “:environment”.', ['environment' => app()->environment()]).$note('environments'),
                                'default' => $defaults['environments'],
                            ]],
                            ['handle' => 'when_off', 'field' => [
                                'type' => 'select',
                                'display' => __('Elsewhere'),
                                'instructions' => __('What happens to content that needs consent where the platform doesn’t load (and on sites with no platform chosen).').$note('when_off'),
                                'options' => ['block' => __('Keep it blocked'), 'run' => __('Run it as if accepted (to test tags)'), 'omit' => __('Leave it out')],
                                'default' => $defaults['when_off'],
                                'clearable' => false,
                            ]],
                            ['handle' => 'auto_insert', 'field' => [
                                'type' => 'toggle',
                                'display' => __('Add to every page by itself'),
                                'instructions' => __('Puts Cookie Connect straight after `<head>` on every front-end page. Turn off only if you’d rather place `{{ consent:head }}` in your layout yourself.').$note('auto_insert'),
                                'default' => $defaults['auto_insert'],
                            ]],
                            ['handle' => 'debug', 'field' => [
                                'type' => 'toggle',
                                'display' => __('Debug in the browser console'),
                                'default' => false,
                            ]],
                        ],
                    ]],
                ],
            ],
        ];
    }
}

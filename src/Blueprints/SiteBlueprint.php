<?php

namespace NineteenNinetyFour\CookieConnect\Blueprints;

use NineteenNinetyFour\CookieConnect\Core\Category;
use NineteenNinetyFour\CookieConnect\Core\Providers\Field;
use NineteenNinetyFour\CookieConnect\Core\Providers\Provider;
use NineteenNinetyFour\CookieConnect\Core\Providers\Providers;
use NineteenNinetyFour\CookieConnect\Core\Settings\Script;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as BlueprintContract;

/**
 * Tools → Cookie Connect: one site's consent platform, Google tags and
 * scripts. Each platform's fields come from its provider file, shown when
 * it's chosen. Handles are flat (cc_cookiebot_cbid, google_gtm_id); toForm()
 * and fromForm() turn them into core's settings and back.
 */
class SiteBlueprint
{
    public const GOOGLE = [
        'gtmId' => 'google_gtm_id',
        'ga4Id' => 'google_ga4_id',
        'consentMode' => 'google_consent_mode',
        'mode' => 'google_mode',
        'regions' => 'google_regions',
        'gtmSetsDefaults' => 'google_gtm_sets_defaults',
        'adsDataRedaction' => 'google_ads_data_redaction',
        'urlPassthrough' => 'google_url_passthrough',
        'waitForUpdate' => 'google_wait_for_update',
    ];

    public function __construct(protected Providers $providers) {}

    /**
     * @param  array<string, string>  $html  status, seo and preview: HTML shown in the form
     * @param  list<string>  $overridden  dotted paths fixed in config
     */
    public function make(array $html = [], array $overridden = [], ?Provider $current = null, array $googleNotes = []): BlueprintContract
    {
        $fixed = fn (string $path) => in_array($path, $overridden, true)
            ? ['read_only' => true, 'instructions' => __('Set in config/cookie-connect.php.')]
            : [];

        $platform = [
            $this->field('status', ['type' => 'html', 'html' => $html['status'] ?? '', 'hide_display' => true]),
            $this->field('provider', [
                'type' => 'select',
                'display' => __('Platform'),
                'instructions' => __('The cookie consent platform this site uses. Switching later needs no template changes.'),
                'options' => ['none' => __('None')] + $this->providers->options(),
                'clearable' => false,
                ...$fixed('provider'),
            ]),
        ];

        foreach ($this->providers->all() as $provider) {
            foreach ($provider->fields as $field) {
                $platform[] = $this->field(self::handle($provider, $field), [
                    ...$this->providerField($field),
                    'if' => ['provider' => 'equals '.$provider->handle],
                    ...$fixed('values.'.$field->handle),
                ]);
            }

            if ($provider->notes !== [] || $provider->docs !== []) {
                $platform[] = $this->field('notes_'.self::key($provider), [
                    'type' => 'html',
                    'hide_display' => true,
                    'html' => $this->notes($provider),
                    'if' => ['provider' => 'equals '.$provider->handle],
                ]);
            }
        }

        $google = [];

        if (($html['seo'] ?? '') !== '') {
            $google[] = $this->field('google_notes', ['type' => 'html', 'html' => $html['seo'], 'hide_display' => true]);
        }

        $google = [...$google,
            $this->field('google_gtm_id', ['type' => 'text', 'display' => __('Google Tag Manager container ID'), 'instructions' => __('Looks like GTM-ABC1234. You can paste the whole snippet.'), 'placeholder' => 'GTM-ABC1234', 'width' => 50, ...$fixed('google.gtmId')]),
            $this->field('google_ga4_id', ['type' => 'text', 'display' => __('Google Analytics 4 measurement ID'), 'instructions' => __('Looks like G-ABC123XYZ9. Leave empty if Google Analytics runs through Tag Manager.'), 'placeholder' => 'G-ABC123XYZ9', 'width' => 50, ...$fixed('google.ga4Id')]),
            $this->field('google_consent_mode', ['type' => 'toggle', 'display' => __('Google Consent Mode v2'), 'instructions' => __('Tells Google’s tags what each visitor allowed. Leave on unless you never use Google tags.'), 'default' => true, ...$fixed('google.consentMode')]),
            $this->field('google_mode', ['type' => 'button_group', 'display' => __('Mode'), 'options' => ['advanced' => __('Advanced'), 'basic' => __('Basic')], 'default' => 'advanced', 'instructions' => __('Advanced: Google’s tags load and send cookieless pings until consent. Basic: Google Analytics loads only after consent.'), 'width' => 50, 'if' => ['google_consent_mode' => true], ...$fixed('google.mode')]),
            $this->field('google_regions', ['type' => 'button_group', 'display' => __('Ask for consent'), 'options' => ['everywhere' => __('Everywhere'), 'eea' => __('EEA, UK, Switzerland')], 'default' => 'everywhere', 'width' => 50, 'if' => ['google_consent_mode' => true], ...$fixed('google.regions')]),
            $this->field('google_gtm_sets_defaults', ['type' => 'toggle', 'display' => __('My Tag Manager container sets the defaults'), 'instructions' => __('Turn on if your container has a consent platform template on the Consent Initialization trigger, so the defaults aren’t set twice.'), 'width' => 50, 'if' => ['google_consent_mode' => true], ...$fixed('google.gtmSetsDefaults')]),
            $this->field('google_ads_data_redaction', ['type' => 'toggle', 'display' => __('Redact ad data when ads are declined'), 'default' => true, 'width' => 50, 'if' => ['google_consent_mode' => true], ...$fixed('google.adsDataRedaction')]),
            $this->field('google_url_passthrough', ['type' => 'toggle', 'display' => __('Pass ad click information through URLs'), 'width' => 50, 'if' => ['google_consent_mode' => true], ...$fixed('google.urlPassthrough')]),
            $this->field('google_wait_for_update', ['type' => 'integer', 'display' => __('Wait for the consent platform (milliseconds)'), 'instructions' => __('How long Google’s tags wait for the platform’s answer. Leave empty for the platform’s own recommendation.'), 'placeholder' => (string) ($current?->waitForUpdate() ?? 500), 'width' => 50, 'if' => ['google_consent_mode' => true], ...$fixed('google.waitForUpdate')]),
        ];

        $categories = [];

        foreach (Category::optional() as $category) {
            $categories[] = $this->field('map_'.$category->value, [
                'type' => 'text',
                'display' => __(ucfirst($category->value)),
                'placeholder' => $current?->categories[$category->value] ?? '',
                'width' => 33,
                ...$fixed('categoryMap.'.$category->value),
            ]);
        }

        return Blueprint::make()->setContents([
            'tabs' => [
                'main' => [
                    'display' => __('Cookie Connect'),
                    'sections' => [
                        ['display' => __('Consent platform'), 'fields' => $platform],
                        ['display' => __('Google tags and Consent Mode'), 'instructions' => __('Optional. Add them here rather than in your templates so they load in the right order: Consent Mode defaults first, then your consent platform, then Google.'), 'fields' => $google],
                        ['display' => __('Scripts'), 'instructions' => __('Paste tracking and embed codes here (Hotjar, a Meta pixel, a chat widget) and they’ll wait for the visitor’s consent, with no template changes.'), 'fields' => [$this->scripts($fixed('scripts'))]],
                        ['display' => __('Category mapping'), 'instructions' => __('The id your platform uses for each of Cookie Connect’s categories. Only change these if you renamed or added categories in your platform.'), 'collapsible' => true, 'collapsed' => true, 'fields' => $categories],
                        ['display' => __('What’s added to each page'), 'collapsible' => true, 'collapsed' => true, 'fields' => [$this->field('preview', ['type' => 'html', 'html' => $html['preview'] ?? '', 'hide_display' => true])]],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Core's settings as the form's values.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function toForm(array $settings): array
    {
        $values = ['provider' => ($settings['provider'] ?? null) ?: 'none'];
        $provider = $this->providers->get($settings['provider'] ?? null);

        foreach ($this->providers->all() as $p) {
            foreach ($p->fields as $field) {
                $value = $provider?->handle === $p->handle ? ($settings['values'][$field->handle] ?? null) : null;
                $default = is_string($field->default) && str_contains($field->default, '{') ? null : $field->default;
                $values[self::handle($p, $field)] = $value ?? $default;
            }
        }

        foreach (self::GOOGLE as $key => $handle) {
            if (array_key_exists($key, $settings['google'] ?? [])) {
                $values[$handle] = $settings['google'][$key];
            }
        }

        foreach (Category::optional() as $category) {
            $values['map_'.$category->value] = $settings['categoryMap'][$category->value] ?? null;
        }

        $values['scripts'] = array_map(fn (array $script) => [
            'name' => $script['name'] ?? '',
            'category' => $script['categories'][0] ?? 'marketing',
            'placement' => $script['placement'] ?? 'head',
            'code' => $script['code'] ?? '',
            'enabled' => $script['enabled'] ?? true,
        ], $settings['scripts'] ?? []);

        return $values;
    }

    /**
     * The form's values as core's settings.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function fromForm(array $values): array
    {
        $provider = $this->providers->get(in_array($values['provider'] ?? null, [null, '', 'none'], true) ? null : $values['provider']);
        $settings = ['provider' => $provider?->handle, 'values' => [], 'google' => [], 'categoryMap' => [], 'scripts' => []];

        foreach ($provider?->fields ?? [] as $field) {
            $settings['values'][$field->handle] = $values[self::handle($provider, $field)] ?? null;
        }

        foreach (self::GOOGLE as $key => $handle) {
            $settings['google'][$key] = $values[$handle] ?? null;
        }

        foreach (Category::optional() as $category) {
            $settings['categoryMap'][$category->value] = $values['map_'.$category->value] ?? null;
        }

        foreach ((array) ($values['scripts'] ?? []) as $row) {
            $settings['scripts'][] = [
                'name' => $row['name'] ?? '',
                'categories' => [$row['category'] ?? 'marketing'],
                'placement' => $row['placement'] ?? 'head',
                'code' => is_array($row['code'] ?? null) ? ($row['code']['code'] ?? '') : ($row['code'] ?? ''),
                'enabled' => $row['enabled'] ?? true,
            ];
        }

        return $settings;
    }

    /**
     * The form field a core problem path belongs to.
     */
    public function errorKey(string $path, ?Provider $provider): string
    {
        [$group, $key] = array_pad(explode('.', $path, 3), 2, '');

        return match ($group) {
            'values' => $provider && ($field = $provider->field($key)) ? self::handle($provider, $field) : 'provider',
            'google' => self::GOOGLE[$key] ?? 'google_gtm_id',
            'categoryMap' => 'map_'.$key,
            'scripts' => 'scripts',
            default => 'provider',
        };
    }

    public static function handle(Provider $provider, Field $field): string
    {
        return 'cc_'.self::key($provider).'_'.$field->handle;
    }

    protected static function key(Provider $provider): string
    {
        return str_replace('-', '_', $provider->handle);
    }

    /**
     * @return array<string, mixed>
     */
    protected function providerField(Field $field): array
    {
        $config = [
            'display' => __($field->label),
            'instructions' => $field->instructions ? __($field->instructions) : null,
            'validate' => $field->required ? ['required'] : [],
        ];

        return $config + match ($field->type) {
            'toggle' => ['type' => 'toggle', 'default' => (bool) $field->default],
            'select' => ['type' => 'select', 'options' => $field->options, 'default' => $field->default, 'clearable' => false],
            'code' => ['type' => 'textarea', 'placeholder' => $field->example],
            default => ['type' => 'text', 'placeholder' => $field->example, 'input_type' => 'text'],
        };
    }

    /**
     * @param  array<string, mixed>  $fixed
     * @return array<string, mixed>
     */
    protected function scripts(array $fixed): array
    {
        return $this->field('scripts', [
            'type' => 'grid',
            'display' => __('Scripts'),
            'hide_display' => true,
            'mode' => 'stacked',
            'add_row' => __('Add a script'),
            'reorderable' => true,
            ...$fixed,
            'fields' => [
                $this->field('name', ['type' => 'text', 'display' => __('Name'), 'width' => 50, 'validate' => ['required']]),
                $this->field('category', ['type' => 'select', 'display' => __('Needs'), 'width' => 25, 'clearable' => false, 'default' => 'marketing', 'options' => collect(Category::cases())->mapWithKeys(fn (Category $c) => [$c->value => __(ucfirst($c->value))])->all()]),
                $this->field('placement', ['type' => 'select', 'display' => __('Where'), 'width' => 25, 'clearable' => false, 'default' => 'head', 'options' => array_combine(Script::PLACEMENTS, [__('Head'), __('Body start'), __('Body end')])]),
                $this->field('code', ['type' => 'textarea', 'display' => __('Code'), 'instructions' => __('Including its `<script>` tags.'), 'validate' => ['required']]),
                $this->field('enabled', ['type' => 'toggle', 'display' => __('On'), 'default' => true]),
            ],
        ]);
    }

    protected function notes(Provider $provider): string
    {
        $html = '';

        foreach ($provider->notes as $note) {
            $html .= '<p>'.e(__($note)).'</p>';
        }

        $checked = e(__('Checked against :platform’s documentation on :date.', ['platform' => $provider->name, 'date' => $provider->verified]));
        $link = $provider->docs === [] ? '' : ' <a href="'.e($provider->docs[0]).'" target="_blank" rel="noopener">'.e(__('Their docs')).'</a>';

        return '<div class="text-sm text-gray-700 dark:text-gray-400 space-y-1">'.$html.'<p class="text-gray-500">'.$checked.$link.'</p></div>';
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function field(string $handle, array $config): array
    {
        return ['handle' => $handle, 'field' => array_filter($config, fn ($value) => $value !== null)];
    }
}

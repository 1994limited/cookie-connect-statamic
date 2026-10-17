<?php

namespace NineteenNinetyFour\CookieConnect;

use Illuminate\Support\Facades\File;
use NineteenNinetyFour\CookieConnect\Core\Providers\Providers;
use NineteenNinetyFour\CookieConnect\Core\Settings\Settings as CoreSettings;
use NineteenNinetyFour\CookieConnect\Core\Settings\Validator;
use Statamic\Sites\Site;
use Symfony\Component\Yaml\Yaml;

/**
 * Each site's consent platform, its values, the Google tags and the
 * Control Panel's scripts: one YAML file per site (config cookie-connect.path),
 * with config/cookie-connect.php's `sites` laid over it.
 */
class SiteSettings
{
    /** The keys a site's file keeps. */
    public const KEYS = ['provider', 'values', 'categoryMap', 'google', 'scripts'];

    /** @var array<string, array<string, mixed>> */
    protected array $stored = [];

    public function __construct(protected Settings $settings) {}

    public function path(Site $site): string
    {
        return rtrim((string) config('cookie-connect.path', resource_path('cookie-connect')), '/').'/'.$site->handle().'.yaml';
    }

    /**
     * What the Control Panel saved for a site.
     *
     * @return array<string, mixed>
     */
    public function stored(Site $site): array
    {
        if (! array_key_exists($site->handle(), $this->stored)) {
            $path = $this->path($site);
            $data = File::exists($path) ? Yaml::parse((string) File::get($path)) : [];
            $this->stored[$site->handle()] = is_array($data) ? $data : [];
        }

        return $this->stored[$site->handle()];
    }

    /**
     * What config sets for a site: '*', then the site's own handle.
     *
     * @return array<string, mixed>
     */
    public function overrides(Site $site): array
    {
        $sites = (array) config('cookie-connect.sites', []);

        return array_replace_recursive(
            is_array($sites['*'] ?? null) ? $sites['*'] : [],
            is_array($sites[$site->handle()] ?? null) ? $sites[$site->handle()] : [],
        );
    }

    /**
     * The dotted paths config sets for a site ("provider", "values.cbid").
     *
     * @return list<string>
     */
    public function overridden(Site $site): array
    {
        return $this->paths($this->overrides($site));
    }

    public function forSite(Site $site): CoreSettings
    {
        $overrides = $this->overrides($site);
        $data = array_replace_recursive($this->stored($site), $overrides);

        if (array_key_exists('scripts', $overrides)) {
            $data['scripts'] = $overrides['scripts'];
        }

        return CoreSettings::fromArray([
            ...$data,
            'active' => $data['active'] ?? $this->settings->runsHere(),
            'whenOff' => $data['whenOff'] ?? $this->settings->whenOff(),
            'debug' => $data['debug'] ?? $this->settings->debug(),
        ]);
    }

    /**
     * Cleans up what was entered for a site and says what's wrong with it,
     * keyed by dotted path, without saving.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public function check(Site $site, array $data): array
    {
        $validator = new Validator(Providers::default());
        $normalised = $validator->normalise(CoreSettings::fromArray($data));
        $saved = array_intersect_key($normalised->toArray(), array_flip(self::KEYS));

        $merged = array_replace_recursive($saved, $this->overrides($site));

        return [$saved, $validator->problems(CoreSettings::fromArray($merged))];
    }

    /**
     * @param  array<string, mixed>  $saved  as check() returned it
     */
    public function save(Site $site, array $saved): void
    {
        File::ensureDirectoryExists(dirname($this->path($site)));
        File::put($this->path($site), Yaml::dump($saved, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));

        unset($this->stored[$site->handle()]);
        app(Output::class)->forget();
    }

    public function forget(): void
    {
        $this->stored = [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected function paths(array $data, string $prefix = ''): array
    {
        $paths = [];

        foreach ($data as $key => $value) {
            $path = $prefix.$key;
            $paths[] = $path;

            if (is_array($value) && ! array_is_list($value) && in_array($key, ['values', 'google', 'categoryMap'], true)) {
                array_push($paths, ...$this->paths($value, $path.'.'));
            }
        }

        return $paths;
    }
}

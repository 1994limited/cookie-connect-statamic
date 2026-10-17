<?php

namespace NineteenNinetyFour\CookieConnect;

use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;
use Throwable;

/**
 * The addon settings (Tools → Addons → Cookie Connect → Settings): where the
 * consent platform loads, what happens elsewhere, adding to pages and
 * debugging. config/cookie-connect.php wins over them. Each site's platform
 * and IDs are kept separately (SiteSettings).
 */
class Settings
{
    public const PACKAGE = '1994/cookie-connect-statamic';

    protected ?array $saved = null;

    public static function defaults(): array
    {
        return [
            'environments' => 'production',
            'when_off' => 'block',
            'auto_insert' => true,
            'debug' => false,
        ];
    }

    /**
     * The addon settings as saved.
     */
    public function saved(): array
    {
        if ($this->saved !== null) {
            return $this->saved;
        }

        try {
            $addon = Addon::get(self::PACKAGE);
            $saved = $addon ? app(SettingsRepository::class)->find($addon->id())?->raw() : null;
        } catch (Throwable) {
            $saved = null;
        }

        return $this->saved = is_array($saved) ? $saved : [];
    }

    public function get(string $key): mixed
    {
        $config = config('cookie-connect.'.$key);

        if ($config !== null) {
            return $config;
        }

        $saved = $this->saved()[$key] ?? null;

        return $saved === null || $saved === '' ? static::defaults()[$key] ?? null : $saved;
    }

    /**
     * The settings config/cookie-connect.php fixes.
     *
     * @return list<string>
     */
    public function overridden(): array
    {
        return array_values(array_filter(array_keys(static::defaults()), fn ($key) => config('cookie-connect.'.$key) !== null));
    }

    /**
     * @return list<string>|string '*' for everywhere
     */
    public function environments(): array|string
    {
        $value = $this->get('environments');

        if (is_string($value)) {
            $value = trim($value) === '*' ? '*' : preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $value === '*' || $value === ['*'] ? '*' : array_values(array_map('strval', (array) $value));
    }

    /**
     * Whether the consent platform loads in this environment (APP_ENV).
     */
    public function runsHere(): bool
    {
        $environments = $this->environments();

        return $environments === '*' || in_array(app()->environment(), $environments, true);
    }

    public function whenOff(): string
    {
        $value = (string) $this->get('when_off');

        return in_array($value, ['block', 'run', 'omit'], true) ? $value : 'block';
    }

    public function autoInsert(): bool
    {
        return filter_var($this->get('auto_insert'), FILTER_VALIDATE_BOOLEAN);
    }

    public function debug(): bool
    {
        return filter_var($this->get('debug'), FILTER_VALIDATE_BOOLEAN);
    }

    public function refresh(): static
    {
        $this->saved = null;

        return $this;
    }
}

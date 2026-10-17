<?php

namespace NineteenNinetyFour\CookieConnect;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use NineteenNinetyFour\CookieConnect\Blueprints\SettingsBlueprint;
use NineteenNinetyFour\CookieConnect\Http\Middleware\InjectCookieConnect;
use NineteenNinetyFour\CookieConnect\Modifiers\Consent as ConsentModifier;
use NineteenNinetyFour\CookieConnect\Modifiers\ConsentTokens;
use NineteenNinetyFour\CookieConnect\Tags\Consent;
use Statamic\Events\AddonSettingsSaved;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Facades\Site;
use Statamic\Facades\StaticCache;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    /** The Control Panel nav icon (resources/brand/nav-icon.svg). */
    public const ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path d="M7 .75a6.25 6.25 0 1 0 6.25 6.25 1.75 1.75 0 0 1-1.9-1.75 1.75 1.75 0 0 1-1.85-1.85A1.75 1.75 0 0 1 7.75 1.5 1.1 1.1 0 0 1 7 .75Z" stroke="currentColor" stroke-linejoin="round"/><circle cx="4.5" cy="5.5" r=".75" fill="currentColor"/><circle cx="8.5" cy="8.5" r=".75" fill="currentColor"/><circle cx="5" cy="9.5" r=".6" fill="currentColor"/></svg>';

    public const PERMISSION = 'manage cookie connect';

    protected $tags = [Consent::class];

    protected $modifiers = [ConsentModifier::class, ConsentTokens::class];

    /** After Statamic's static caching in the group, so cached pages have it. */
    protected $middlewareGroups = [
        'statamic.web' => [InjectCookieConnect::class],
    ];

    protected $config = false;

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/cookie-connect.php', 'cookie-connect');

        $this->app->singleton(Settings::class);
        $this->app->singleton(SiteSettings::class);
        $this->app->scoped(Output::class);
    }

    public function bootAddon(): void
    {
        $this->publishes([__DIR__.'/../config/cookie-connect.php' => config_path('cookie-connect.php')], 'cookie-connect-config');

        $this->registerSettingsBlueprint(fn () => app(SettingsBlueprint::class)->contents());

        Permission::extend(function () {
            Permission::group('cookie-connect', 'Cookie Connect', function () {
                Permission::register(self::PERMISSION)->label(__('Choose the consent platform and edit Cookie Connect’s settings for each site'));
            });
        });

        Nav::extend(function ($nav) {
            $item = $nav->tools(__('Cookie Connect'))
                ->route('cookie-connect.index')
                ->icon(self::ICON)
                ->can(self::PERMISSION);

            if (Site::multiEnabled()) {
                $item->children(Site::all()->mapWithKeys(fn ($site) => [$site->name() => cp_route('cookie-connect.edit', $site->handle())])->all());
            }
        });

        // @consent('analytics') … @endconsent
        Blade::directive('consent', fn (string $expression) => "<?php \$__cookieConnect[] = [{$expression}]; ob_start(); ?>");
        Blade::directive('endconsent', fn () => '<?php $__cookieConnectArgs = array_pop($__cookieConnect); echo app(\\'.Output::class.'::class)->gate(ob_get_clean(), ...$__cookieConnectArgs); ?>');

        Event::listen(AddonSettingsSaved::class, function (AddonSettingsSaved $event) {
            if ($event->settings->addon()->package() === Settings::PACKAGE) {
                app(Settings::class)->refresh();
                app(Output::class)->forget();

                if (config('statamic.static_caching.strategy')) {
                    StaticCache::flush();
                }
            }
        });
    }
}

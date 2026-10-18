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
    public const ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path d="M7 1.6A5.6 5.6 0 1 0 12.6 7.2 3.3 3.3 0 0 1 8.95 3.75 3.3 3.3 0 0 1 7 1.6Z" stroke="currentColor" stroke-width="1" stroke-linejoin="round"></path><path d="M10.4 1.4H12.6V3.6Z" stroke="currentColor" stroke-width="1" stroke-linejoin="round"></path><circle cx="4.7" cy="5.6" r=".75" fill="currentColor"></circle><circle cx="8.4" cy="8.8" r=".75" fill="currentColor"></circle><circle cx="5.1" cy="9.7" r=".55" fill="currentColor"></circle></svg>';

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

        // Per request, so long-lived workers (Octane) pick up saved changes.
        $this->app->scoped(Settings::class);
        $this->app->scoped(SiteSettings::class);
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

<?php

namespace NineteenNinetyFour\CookieConnect\Tests;

use Illuminate\Support\Facades\File;
use NineteenNinetyFour\CookieConnect\Output;
use NineteenNinetyFour\CookieConnect\ServiceProvider;
use NineteenNinetyFour\CookieConnect\Settings;
use NineteenNinetyFour\CookieConnect\SiteSettings;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;
use Statamic\Facades\Site;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('cookie-connect.path', self::path());
        // Run everywhere unless a test says otherwise: the tests' APP_ENV is "testing".
        $app['config']->set('cookie-connect.environments', '*');
        $app['config']->set('view.paths', [__DIR__.'/__fixtures__/views']);
    }

    public static function path(): string
    {
        return __DIR__.'/__fixtures__/dev-null/sites';
    }

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(self::path());
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory(self::path()));

        $this->clearAddonSettings();
        $this->beforeApplicationDestroyed(fn () => $this->clearAddonSettings());
    }

    protected function clearAddonSettings(): void
    {
        $addon = Addon::get(Settings::PACKAGE);

        if ($addon && ($settings = app(SettingsRepository::class)->find($addon->id()))) {
            app(SettingsRepository::class)->delete($settings);
        }

        app(Settings::class)->refresh();
    }

    /**
     * Saves a site's settings as the Control Panel would, and forgets what was rendered.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string> the problems, when nothing was saved
     */
    protected function saveSite(array $data, string $site = 'default'): array
    {
        $sites = app(SiteSettings::class);
        [$saved, $problems] = $sites->check(Site::get($site), $data);

        if ($problems === []) {
            $sites->save(Site::get($site), $saved);
        }

        app(Output::class)->forget();

        return $problems;
    }

    protected function cookiebot(array $data = [], string $site = 'default'): void
    {
        $this->assertSame([], $this->saveSite(array_replace_recursive([
            'provider' => 'cookiebot',
            'values' => ['cbid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'],
        ], $data), $site));
    }

    protected function multisite(): void
    {
        Site::setSites([
            'default' => ['name' => 'Dog-ear Books', 'url' => 'https://www.dogear.test/', 'locale' => 'en_GB'],
            'german' => ['name' => 'Eselsohr Bücher', 'url' => 'https://eselsohr.test/', 'locale' => 'de_DE'],
        ]);
    }
}

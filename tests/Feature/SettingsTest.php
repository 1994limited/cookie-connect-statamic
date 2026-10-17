<?php

namespace NineteenNinetyFour\CookieConnect\Tests\Feature;

use NineteenNinetyFour\CookieConnect\Output;
use NineteenNinetyFour\CookieConnect\Settings;
use NineteenNinetyFour\CookieConnect\SiteSettings;
use NineteenNinetyFour\CookieConnect\Tests\TestCase;
use Statamic\Facades\Site;

class SettingsTest extends TestCase
{
    public function test_it_runs_only_in_the_environments_chosen(): void
    {
        config(['cookie-connect.environments' => null]);
        $this->assertSame(['production'], app(Settings::class)->environments());
        $this->assertFalse(app(Settings::class)->runsHere());

        config(['cookie-connect.environments' => 'production, testing']);
        $this->assertTrue(app(Settings::class)->runsHere());

        config(['cookie-connect.environments' => '*']);
        $this->assertSame('*', app(Settings::class)->environments());
    }

    public function test_config_is_reported_as_overriding_the_addon_settings(): void
    {
        config(['cookie-connect.when_off' => 'run']);

        $this->assertSame('run', app(Settings::class)->whenOff());
        $this->assertSame(['environments', 'when_off'], app(Settings::class)->overridden());
    }

    public function test_a_pasted_snippet_is_saved_as_its_id(): void
    {
        $this->assertSame([], $this->saveSite(['provider' => 'termly', 'values' => [
            'websiteUuid' => '<script src="https://app.termly.io/resource-blocker/1A2B3C4D-5E6F-7A8B-9C0D-1E2F3A4B5C6D?autoBlock=on"></script>',
        ]]));

        $this->assertSame('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', app(SiteSettings::class)->stored(Site::default())['values']['websiteUuid']);
        $this->assertFileExists(self::path().'/default.yaml');
    }

    public function test_problems_stop_the_save(): void
    {
        $problems = $this->saveSite(['provider' => 'cookiebot', 'values' => ['cbid' => 'nope'], 'google' => ['gtmId' => 'GTM-']]);

        $this->assertSame(['values.cbid', 'google.gtmId'], array_keys($problems));
        $this->assertFileDoesNotExist(self::path().'/default.yaml');
    }

    public function test_each_site_has_its_own_platform(): void
    {
        $this->multisite();
        $this->cookiebot();
        $this->assertSame([], $this->saveSite(['provider' => 'usercentrics', 'values' => ['settingsId' => 'y05NPaJsbGnT2v']], 'german'));

        $this->assertSame('cookiebot', app(SiteSettings::class)->forSite(Site::get('default'))->provider);
        $this->assertStringContainsString('data-language="de"', app(Output::class)->renderer(Site::get('german'))->head());
        $this->assertSame('dogear.test', app(Output::class)->context(Site::get('default'))->host);
    }

    public function test_config_sites_win_and_are_reported(): void
    {
        $this->cookiebot();
        config(['cookie-connect.sites' => ['*' => ['google' => ['gtmId' => 'GTM-XYZ9876']]]]);

        $settings = app(SiteSettings::class)->forSite(Site::default());

        $this->assertSame('cookiebot', $settings->provider);
        $this->assertSame('GTM-XYZ9876', $settings->google->gtmId);
        $this->assertSame(['google', 'google.gtmId'], app(SiteSettings::class)->overridden(Site::default()));
    }

    public function test_the_nonce_comes_from_config(): void
    {
        $this->cookiebot();
        config(['cookie-connect.nonce' => fn () => 'n0nce']);
        app(Output::class)->forget();

        $this->assertStringContainsString('async nonce="n0nce">', app(Output::class)->renderer()->head());
    }
}

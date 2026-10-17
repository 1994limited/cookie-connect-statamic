<?php

namespace NineteenNinetyFour\CookieConnect\Tests\Feature;

use NineteenNinetyFour\CookieConnect\SiteSettings;
use NineteenNinetyFour\CookieConnect\Tests\TestCase;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class ControlPanelTest extends TestCase
{
    private function signIn(array $permissions = [], bool $super = false)
    {
        $role = Role::make('editor')->permissions(['access cp', ...$permissions]);
        $role->save();
        $user = User::make()->email(bin2hex(random_bytes(3)).'@example.com');
        $super ? $user->makeSuper() : $user->assignRole('editor');

        $user->save();

        return $this->actingAs($user);
    }

    public function test_the_page_needs_the_permission(): void
    {
        $this->signIn();

        $this->getJson(cp_route('cookie-connect.index'))->assertForbidden();
    }

    public function test_an_editor_with_the_permission_sees_the_form(): void
    {
        $this->signIn(['manage cookie connect']);
        $this->cookiebot();

        $data = $this->getJson(cp_route('cookie-connect.edit', 'default'))->assertOk()->json();

        $this->assertSame('cookiebot', $data['values']['provider']);
        $this->assertSame('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', $data['values']['cc_cookiebot_cbid']);
        $this->assertStringContainsString('Cookiebot is live on this site.', $data['blueprint']['tabs'][0]['sections'][0]['fields'][0]['html']);
    }

    public function test_saving_normalises_and_validates_by_field(): void
    {
        $this->signIn(['manage cookie connect']);

        $this->patchJson(cp_route('cookie-connect.update', 'default'), [
            'provider' => 'cookiebot',
            'cc_cookiebot_cbid' => 'nope',
            'google_gtm_id' => 'GTM-',
        ])->assertStatus(422)->assertJsonValidationErrors(['cc_cookiebot_cbid', 'google_gtm_id']);

        $this->patchJson(cp_route('cookie-connect.update', 'default'), [
            'provider' => 'cookiebot',
            'cc_cookiebot_cbid' => 'data-cbid="1A2B3C4D-5E6F-7A8B-9C0D-1E2F3A4B5C6D"',
            'cc_cookiebot_cdn' => 'eu',
            'cc_onetrust_domainScript' => 'ignored',
            'google_gtm_id' => "'GTM-AB12CD3'",
            'scripts' => [['name' => 'Hotjar', 'category' => 'analytics', 'placement' => 'head', 'code' => '<script>hj()</script>', 'enabled' => true]],
        ])->assertOk()->assertJson(['saved' => true]);

        $stored = app(SiteSettings::class)->stored(Site::default());
        $this->assertSame('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', $stored['values']['cbid']);
        $this->assertSame('eu', $stored['values']['cdn']);
        $this->assertArrayNotHasKey('domainScript', $stored['values']);
        $this->assertSame('GTM-AB12CD3', $stored['google']['gtmId']);
        $this->assertSame(['analytics'], $stored['scripts'][0]['categories']);
    }

    public function test_none_clears_the_platform(): void
    {
        $this->signIn([], super: true);
        $this->cookiebot();

        $this->patchJson(cp_route('cookie-connect.update', 'default'), ['provider' => 'none'])->assertOk();

        $this->assertNull(app(SiteSettings::class)->stored(Site::default())['provider']);
    }
}

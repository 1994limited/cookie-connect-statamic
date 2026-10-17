<?php

namespace NineteenNinetyFour\CookieConnect\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use NineteenNinetyFour\CookieConnect\Output;
use NineteenNinetyFour\CookieConnect\Tests\TestCase;
use Statamic\Facades\Antlers;

class TagsTest extends TestCase
{
    private function antlers(string $template, array $data = []): string
    {
        return trim((string) Antlers::parse($template, $data, true));
    }

    public function test_the_consent_tag_uses_the_platforms_markup(): void
    {
        $this->cookiebot();

        $this->assertSame(
            '<script type="text/plain" data-cookieconsent="statistics" data-cookie-connect-native="analytics">track(1)</script>',
            $this->antlers('{{ consent:analytics }}<script>track({{ n }})</script>{{ /consent:analytics }}', ['n' => 1]),
        );
    }

    public function test_switching_platform_needs_no_template_change(): void
    {
        $template = '{{ consent:analytics }}<script>track()</script>{{ /consent:analytics }}';

        $this->saveSite(['provider' => 'onetrust', 'values' => ['domainScript' => 'fff8df06-1dd2-491b-88f6-01cae248cd17']]);
        $this->assertStringContainsString('class="optanon-category-C0002"', $this->antlers($template));

        $this->saveSite(['provider' => 'cookie-script', 'values' => ['scriptId' => 'b62c08ab61b84e127d792f54b324dd70']]);
        $this->assertStringContainsString('data-cookiecategory="performance"', $this->antlers($template));
    }

    public function test_several_categories_and_a_placeholder(): void
    {
        $this->cookiebot();

        $html = $this->antlers('{{ consent categories="analytics|marketing" placeholder="true" }}<iframe src="https://x.test"></iframe>{{ /consent }}');

        $this->assertStringContainsString('<div class="cookie-connect-placeholder" data-cookie-connect-placeholder="analytics marketing"><p>This content needs analytics &amp; marketing cookies.', $html);
        $this->assertStringContainsString('data-cookieconsent="statistics,marketing"', $html);

        $custom = $this->antlers('{{ consent:marketing placeholder="Allow marketing cookies to watch." }}<iframe src="https://x.test"></iframe>{{ /consent:marketing }}');
        $this->assertStringContainsString('<p>Allow marketing cookies to watch.</p>', $custom);
    }

    public function test_the_modifiers(): void
    {
        $this->cookiebot();

        $this->assertStringContainsString('data-cookieconsent="marketing"', $this->antlers('{{ embed | consent:marketing }}', ['embed' => '<script>a()</script>']));
        $this->assertStringContainsString('data-cookieconsent="statistics,marketing"', $this->antlers('{{ embed | consent:analytics:marketing }}', ['embed' => '<script>a()</script>']));
        $this->assertStringContainsString('id="CookieDeclaration"', $this->antlers('{{ body | consent_tokens }}', ['body' => '<p>{cookie_declaration}</p>']));
    }

    public function test_the_other_tags(): void
    {
        $this->cookiebot();

        $this->assertStringStartsWith('<!-- Cookie Connect -->', $this->antlers('{{ consent:head }}'));
        $this->assertStringContainsString('id="CookieDeclaration"', $this->antlers('{{ consent:declaration }}'));
        $this->assertSame('<button type="button" class="cookie-connect-settings" data-cookie-connect-open>Cookie settings</button>', $this->antlers('{{ consent:settings_link }}'));
        $this->assertSame('<a href="#cookie-settings" class="cookie-connect-settings footer" data-cookie-connect-open>Privacy</a>', $this->antlers('{{ consent:settings_link text="Privacy" class="footer" as="a" }}'));
        $this->assertStringContainsString('data-cookie-connect-placeholder="marketing"', $this->antlers('{{ consent:placeholder category="marketing" }}'));
        $this->assertStringContainsString("'sha256-", $this->antlers('{{ consent:head }}{{ consent:hashes }}'));
    }

    public function test_blade(): void
    {
        $this->cookiebot();

        $this->assertStringContainsString(
            'data-cookieconsent="statistics" data-cookie-connect-native="analytics">track()</script>',
            Blade::render('@consent(\'analytics\')<script>track()</script>@endconsent'),
        );
        $this->assertStringContainsString(
            'data-cookieconsent="marketing" data-cookie-connect-native="marketing">ad()</script>',
            Blade::render('<s:consent:marketing><script>ad()</script></s:consent:marketing>'),
        );
        $this->assertStringContainsString('class="cookie-connect-settings"', Blade::render('<s:consent:settings_link />'));
        $this->assertStringContainsString('data-cookieconsent="statistics,marketing"', Blade::render("@consent(['analytics', 'marketing'])<script>a()</script>@endconsent"));
        $this->assertStringContainsString('<p>Allow marketing cookies.</p>', Blade::render("@consent('marketing', 'Allow marketing cookies.')<iframe src=\"https://x.test\"></iframe>@endconsent"));
        $this->assertStringContainsString('<p>This content needs marketing cookies.', Blade::render('<s:consent:marketing placeholder="true"><iframe src="https://x.test"></iframe></s:consent:marketing>'));
        $this->assertStringContainsString('data-cookieconsent="marketing"', Blade::render('{!! Statamic::modify($embed)->consent([\'marketing\']) !!}', ['embed' => '<script>a()</script>']));
        $this->assertStringContainsString('id="CookieDeclaration"', Blade::render('{!! Statamic::modify($body)->consentTokens() !!}', ['body' => '<p>{cookie_declaration}</p>']));
        $this->assertStringContainsString('data-cookie-connect-placeholder="marketing"', Blade::render('<s:consent:placeholder category="marketing" />'));
        $this->assertStringContainsString('id="CookieDeclaration"', Blade::render('<s:consent:declaration />'));
    }

    public function test_with_no_platform_content_stays_blocked_or_runs(): void
    {
        $this->assertSame('<script type="text/plain" data-cookie-connect="marketing">a()</script>', $this->antlers('{{ consent:marketing }}<script>a()</script>{{ /consent:marketing }}'));

        config(['cookie-connect.when_off' => 'run']);
        app(Output::class)->forget();
        $this->assertSame('<script>a()</script>', $this->antlers('{{ consent:marketing }}<script>a()</script>{{ /consent:marketing }}'));
    }
}

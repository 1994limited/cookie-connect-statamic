<?php

namespace NineteenNinetyFour\CookieConnect\Tests\Feature;

use Illuminate\Http\Request;
use NineteenNinetyFour\CookieConnect\Http\Middleware\InjectCookieConnect;
use NineteenNinetyFour\CookieConnect\Output;
use NineteenNinetyFour\CookieConnect\Tests\TestCase;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Symfony\Component\HttpFoundation\Response;

class InjectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Collection::make('pages')->routes('/{slug}')->template('page')->layout('layout')->save();
        Entry::make()->collection('pages')->slug('cookies')->data(['title' => 'Cookies', 'content' => '<p>{cookie_declaration}</p>'])->save();
    }

    public function test_pages_get_cookie_connect_with_no_template_changes(): void
    {
        $this->cookiebot(['scripts' => [['name' => 'Hotjar', 'categories' => ['analytics'], 'placement' => 'body_end', 'code' => '<script>hj()</script>']]]);

        $html = $this->get('/cookies')->assertOk()->getContent();

        $this->assertStringContainsString("<head>\n<!-- Cookie Connect -->", $html);
        $this->assertStringContainsString('consent.cookiebot.com/uc.js', $html);
        $this->assertStringContainsString('data-cookieblock-src="https://www.youtube.com/embed/x" data-cookieconsent="marketing"', $html);
        $this->assertStringContainsString('id="CookieDeclaration"', $html);
        $this->assertStringNotContainsString('{cookie_declaration}', $html);
        $this->assertStringContainsString('data-cookieconsent="statistics" data-cookie-connect-native="analytics">hj()</script>', $html);
        $this->assertSame(1, substr_count($html, '<!-- Cookie Connect -->'));
    }

    public function test_with_adding_to_pages_off_nothing_is_added(): void
    {
        $this->cookiebot();
        config(['cookie-connect.auto_insert' => false]);

        $this->assertStringNotContainsString('Cookie Connect', $this->get('/cookies')->getContent());
    }

    private function through(Response $response): string
    {
        return (string) (new InjectCookieConnect)->handle(Request::create('/x'), fn () => $response)->getContent();
    }

    public function test_json_and_fragments_are_left_alone(): void
    {
        $this->cookiebot();

        $this->assertStringNotContainsString('Cookie Connect', $this->through(response()->json(['html' => '<html><head></head></html>'])));
        $this->assertSame('<div>partial</div>', $this->through(response('<div>partial</div>')));
        $this->assertStringContainsString('<!-- Cookie Connect -->', $this->through(response('<html><head></head><body></body></html>')));
    }

    public function test_a_page_already_carrying_the_head_tag_gets_it_once(): void
    {
        $this->cookiebot();
        $page = '<html><head>'.app(Output::class)->renderer()->head().'</head><body></body></html>';

        $this->assertSame(1, substr_count($this->through(response($page)), '<!-- Cookie Connect -->'));
    }

    public function test_static_caching_keeps_it(): void
    {
        config(['statamic.static_caching.strategy' => 'half', 'statamic.static_caching.strategies.half' => ['driver' => 'application', 'expiry' => null]]);
        $this->cookiebot();

        $first = $this->get('/cookies')->getContent();
        $second = $this->get('/cookies')->getContent();

        $this->assertStringContainsString('<!-- Cookie Connect -->', $second);
        $this->assertSame(1, substr_count($second, '<!-- Cookie Connect -->'));
        $this->assertSame($first, $second);
    }
}

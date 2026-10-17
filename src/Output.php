<?php

namespace NineteenNinetyFour\CookieConnect;

use NineteenNinetyFour\CookieConnect\Core\Category;
use NineteenNinetyFour\CookieConnect\Core\Output\Context;
use NineteenNinetyFour\CookieConnect\Core\Output\Injector;
use NineteenNinetyFour\CookieConnect\Core\Output\Renderer;
use Statamic\Facades\Site as Sites;
use Statamic\Sites\Site;

/**
 * Cookie Connect's output for the current site and request: what the tags
 * print, and what goes into each page by itself.
 */
class Output
{
    /** @var array<string, Renderer> */
    protected array $renderers = [];

    protected ?string $nonce = null;

    protected bool $nonceKnown = false;

    public function __construct(protected SiteSettings $sites) {}

    public function renderer(?Site $site = null): Renderer
    {
        $site ??= Sites::current();

        return $this->renderers[$site->handle()] ??= new Renderer($this->sites->forSite($site), $this->context($site));
    }

    public function forget(): void
    {
        $this->renderers = [];
        $this->nonceKnown = false;
    }

    public function context(Site $site): Context
    {
        $host = (string) (parse_url($site->absoluteUrl(), PHP_URL_HOST) ?: request()->getHost());

        return new Context($host, (string) $site->locale(), $this->nonce());
    }

    public function nonce(): ?string
    {
        if (! $this->nonceKnown) {
            $this->nonceKnown = true;
            $nonce = config('cookie-connect.nonce');
            $value = is_callable($nonce) ? $nonce() : $nonce;
            $this->nonce = is_string($value) && $value !== '' ? $value : null;
        }

        return $this->nonce;
    }

    /**
     * Puts Cookie Connect into a whole page for the current site.
     */
    public function inject(string $html): string
    {
        $renderer = $this->renderer();

        return Injector::inject(
            $html,
            $renderer->head($html),
            $renderer->bodyStart(),
            $renderer->bodyEnd(),
            fn (string $body) => $renderer->replaceTokens($body, $this->settingsText()),
        );
    }

    /**
     * @param  string|list<string>  $categories
     * @param  bool|string  $placeholder  true for the standard message, or a message
     */
    public function gate(string $html, string|array $categories, bool|string $placeholder = false): string
    {
        $renderer = $this->renderer();
        $gated = $renderer->gate($html, $categories);

        if ($placeholder !== false && $placeholder !== '' && $gated !== '') {
            $text = is_string($placeholder) && ! in_array(strtolower($placeholder), ['true', '1', 'yes'], true) ? $placeholder : $this->placeholderText($categories);
            $gated = $renderer->placeholder($categories, $text, $this->settingsText()).$gated;
        }

        return $gated;
    }

    public function settingsText(): string
    {
        return __('Cookie settings');
    }

    /**
     * @param  string|list<string>  $categories
     */
    public function placeholderText(string|array $categories): string
    {
        $names = array_map(fn (Category $c) => __($c->value), array_filter(Category::list($categories), fn (Category $c) => $c !== Category::Necessary));

        return __('This content needs :categories cookies. You can allow them in your cookie settings.', ['categories' => implode(' & ', $names)]);
    }
}

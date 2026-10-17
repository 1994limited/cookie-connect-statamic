<?php

namespace NineteenNinetyFour\CookieConnect\Tags;

use NineteenNinetyFour\CookieConnect\Output;
use Statamic\Tags\Tags;

/**
 * {{ consent:analytics }}…{{ /consent:analytics }}
 * {{ consent categories="analytics|marketing" placeholder="true" }}…{{ /consent }}
 * {{ consent:settings_link }}, {{ consent:declaration }}, {{ consent:placeholder category="marketing" }},
 * {{ consent:head }}, {{ consent:body_start }}, {{ consent:body_end }}, {{ consent:hashes }}
 */
class Consent extends Tags
{
    protected static $handle = 'consent';

    /** The tag names that aren't categories. */
    public const RESERVED = ['head', 'body_start', 'body_end', 'declaration', 'settings_link', 'placeholder', 'hashes'];

    /**
     * {{ consent categories="…" }}…{{ /consent }}
     */
    public function index(): string
    {
        return $this->gate($this->params->explode(['categories', 'category', 'needs'], ['marketing']));
    }

    /**
     * {{ consent:analytics }}…{{ /consent:analytics }}
     */
    public function wildcard(string $method): string
    {
        return $this->gate(explode('_', $method));
    }

    public function head(): string
    {
        return $this->output()->renderer()->head();
    }

    public function bodyStart(): string
    {
        return $this->output()->renderer()->bodyStart();
    }

    public function bodyEnd(): string
    {
        return $this->output()->renderer()->bodyEnd();
    }

    public function declaration(): string
    {
        return $this->output()->renderer()->declaration();
    }

    /**
     * {{ consent:settings_link text="Change my cookie choices" class="link" as="a" }}
     */
    public function settingsLink(): string
    {
        $attributes = array_filter(['class' => $this->params->get('class'), 'id' => $this->params->get('id')]);

        return $this->output()->renderer()->settingsLink(
            (string) ($this->params->get('text') ?? ($this->isPair ? trim($this->parse()) : '') ?: $this->output()->settingsText()),
            $attributes,
            $this->params->get('as') === 'a' ? 'a' : 'button',
        );
    }

    /**
     * {{ consent:placeholder category="marketing" text="…" button="…" }}
     */
    public function placeholder(): string
    {
        $categories = $this->params->explode(['categories', 'category', 'needs'], ['marketing']);

        return $this->output()->renderer()->placeholder(
            $categories,
            (string) ($this->params->get('text') ?? $this->output()->placeholderText($categories)),
            (string) ($this->params->get('button') ?? $this->output()->settingsText()),
        );
    }

    /**
     * {{ consent:hashes }} gives the 'sha256-…' sources for a CSP header, space-separated.
     */
    public function hashes(): string
    {
        return implode(' ', $this->output()->renderer()->cspHashes());
    }

    /**
     * @param  list<string>  $categories
     */
    protected function gate(array $categories): string
    {
        $placeholder = $this->params->get('placeholder', false);

        return $this->output()->gate(
            $this->isPair ? (string) $this->parse() : (string) $this->params->get('html', ''),
            $categories,
            is_bool($placeholder) ? $placeholder : (string) $placeholder,
        );
    }

    protected function output(): Output
    {
        return app(Output::class);
    }
}

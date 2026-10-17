<?php

namespace NineteenNinetyFour\CookieConnect\Modifiers;

use NineteenNinetyFour\CookieConnect\Output;
use Statamic\Modifiers\Modifier;

/**
 * {{ content | consent_tokens }} swaps {cookie_declaration} and
 * {cookie_settings}. Only needed when Cookie Connect isn't added to pages
 * by itself, which swaps them anyway.
 */
class ConsentTokens extends Modifier
{
    protected static $handle = 'consent_tokens';

    public function index($value)
    {
        $output = app(Output::class);

        return $output->renderer()->replaceTokens((string) $value, $output->settingsText());
    }
}

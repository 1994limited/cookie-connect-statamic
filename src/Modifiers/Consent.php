<?php

namespace NineteenNinetyFour\CookieConnect\Modifiers;

use NineteenNinetyFour\CookieConnect\Output;
use Statamic\Modifiers\Modifier;

/**
 * {{ embed_code | consent:marketing }}
 * {{ embed_code | consent:analytics:marketing }}
 */
class Consent extends Modifier
{
    protected static $handle = 'consent';

    public function index($value, $params)
    {
        return app(Output::class)->gate((string) $value, $params ?: ['marketing']);
    }
}

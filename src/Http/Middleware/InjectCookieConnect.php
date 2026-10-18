<?php

namespace NineteenNinetyFour\CookieConnect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NineteenNinetyFour\CookieConnect\Core\Output\Injector;
use NineteenNinetyFour\CookieConnect\Output;
use NineteenNinetyFour\CookieConnect\Settings;
use Statamic\Statamic;

/**
 * Puts Cookie Connect into every front-end page. It's in the statamic.web
 * group after Statamic's static caching, so pages are cached with it in.
 */
class InjectCookieConnect
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof Response || Statamic::isCpRoute() || ! app(Settings::class)->autoInsert()) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || ! Injector::isPage($content, $response->headers->get('Content-Type'))) {
            return $response;
        }

        $response->setContent(app(Output::class)->inject($content));

        // The body changed: headers describing the old one would be wrong.
        $response->headers->remove('Content-Length');
        $response->headers->remove('ETag');
        $response->headers->remove('Content-MD5');

        return $response;
    }
}

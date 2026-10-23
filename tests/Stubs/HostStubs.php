<?php

// Minimal stand-ins for classes the host "ME" application provides, so the package
// can be tested in isolation. Only declared when the real classes are absent.

namespace ME\Http\Controllers {
    if (! class_exists(Controller::class)) {
        abstract class Controller extends \Illuminate\Routing\Controller
        {
        }
    }
}

namespace ME\Http\Middleware {
    if (! class_exists(LocaleMiddleware::class)) {
        class LocaleMiddleware
        {
            public function handle($request, \Closure $next)
            {
                return $next($request);
            }
        }
    }
}

namespace ME\Utility\Tests\Stubs {
    class PassThroughMiddleware
    {
        public function handle($request, \Closure $next)
        {
            return $next($request);
        }
    }
}

<?php

namespace ArielMejiaDev\XFactor\Http\Middleware;

use ArielMejiaDev\XFactor\XFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('x-factor.dashboard.enabled', true)) {
            abort(404);
        }

        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! XFactor::check($user)) {
            abort(403);
        }

        return $next($request);
    }
}

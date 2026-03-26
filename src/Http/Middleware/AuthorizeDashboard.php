<?php

namespace ArielMejiaDev\HealingFactor\Http\Middleware;

use ArielMejiaDev\HealingFactor\HealingFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('healing-factor.dashboard.enabled', true)) {
            abort(404);
        }

        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! HealingFactor::check($user)) {
            abort(403);
        }

        return $next($request);
    }
}

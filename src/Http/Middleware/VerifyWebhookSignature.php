<?php

namespace ArielMejiaDev\XFactor\Http\Middleware;

use ArielMejiaDev\XFactor\Contracts\MonitorContract;
use ArielMejiaDev\XFactor\Exceptions\WebhookVerificationFailed;
use ArielMejiaDev\XFactor\Support\XFactorLogger;
use Closure;
use Illuminate\Http\Request;

class VerifyWebhookSignature
{
    public function __construct(protected MonitorContract $monitor) {}

    public function handle(Request $request, Closure $next)
    {
        $secret = config('x-factor.webhook.secret');

        if (empty($secret)) {
            if (app()->environment('local', 'testing')) {
                XFactorLogger::warning('Webhook secret not configured. Skipping signature verification (local/testing only).');

                return $next($request);
            }

            throw new WebhookVerificationFailed('Webhook secret is not configured. Set X_FACTOR_WEBHOOK_SECRET in your environment.');
        }

        if (! $this->monitor->verifySignature($request, $secret)) {
            throw new WebhookVerificationFailed('Invalid webhook signature.');
        }

        return $next($request);
    }
}

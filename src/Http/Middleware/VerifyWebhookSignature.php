<?php

namespace ArielMejiaDev\HealingFactor\Http\Middleware;

use ArielMejiaDev\HealingFactor\Contracts\MonitorContract;
use ArielMejiaDev\HealingFactor\Exceptions\WebhookVerificationFailed;
use ArielMejiaDev\HealingFactor\Support\HealingFactorLogger;
use Closure;
use Illuminate\Http\Request;

class VerifyWebhookSignature
{
    public function __construct(protected MonitorContract $monitor) {}

    public function handle(Request $request, Closure $next)
    {
        $secret = config('healing-factor.webhook.secret');

        if (empty($secret)) {
            if (app()->environment('local', 'testing')) {
                HealingFactorLogger::warning('Webhook secret not configured. Skipping signature verification (local/testing only).');

                return $next($request);
            }

            throw new WebhookVerificationFailed('Webhook secret is not configured. Set HEALING_FACTOR_WEBHOOK_SECRET in your environment.');
        }

        if (! $this->monitor->verifySignature($request, $secret)) {
            throw new WebhookVerificationFailed('Invalid webhook signature.');
        }

        return $next($request);
    }
}

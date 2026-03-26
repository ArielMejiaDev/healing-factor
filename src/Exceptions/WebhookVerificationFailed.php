<?php

namespace ArielMejiaDev\HealingFactor\Exceptions;

use Illuminate\Http\JsonResponse;

class WebhookVerificationFailed extends HealingFactorException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => 'Invalid webhook signature.'], 403);
    }
}

<?php

namespace ArielMejiaDev\XFactor\Exceptions;

use Illuminate\Http\JsonResponse;

class WebhookVerificationFailed extends XFactorException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => 'Invalid webhook signature.'], 403);
    }
}

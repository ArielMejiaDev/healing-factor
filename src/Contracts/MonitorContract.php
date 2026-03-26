<?php

namespace ArielMejiaDev\HealingFactor\Contracts;

use Illuminate\Http\Request;

interface MonitorContract
{
    /** Parse the incoming webhook request into issue data array. Returns null if unparseable. */
    public function parseWebhook(Request $request): ?array;

    /** Determine if this webhook event should be processed. */
    public function shouldProcess(Request $request): bool;

    /** Verify the HMAC/token signature of the request. */
    public function verifySignature(Request $request, string $secret): bool;
}

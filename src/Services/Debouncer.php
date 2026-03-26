<?php

namespace ArielMejiaDev\HealingFactor\Services;

use Illuminate\Support\Facades\Cache;

class Debouncer
{
    public function shouldProcess(string $fingerprint): bool
    {
        $minutes = config('healing-factor.debounce_minutes', 5);
        $key = "healing-factor:debounce:{$fingerprint}";

        return Cache::add($key, true, now()->addMinutes($minutes));
    }

    public function clear(string $fingerprint): void
    {
        Cache::forget("healing-factor:debounce:{$fingerprint}");
    }
}

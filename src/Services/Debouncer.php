<?php

namespace ArielMejiaDev\XFactor\Services;

use Illuminate\Support\Facades\Cache;

class Debouncer
{
    public function shouldProcess(string $fingerprint): bool
    {
        $minutes = config('x-factor.debounce_minutes', 5);
        $key = "x-factor:debounce:{$fingerprint}";

        return Cache::add($key, true, now()->addMinutes($minutes));
    }

    public function clear(string $fingerprint): void
    {
        Cache::forget("x-factor:debounce:{$fingerprint}");
    }
}

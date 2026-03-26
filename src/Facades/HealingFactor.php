<?php

namespace ArielMejiaDev\HealingFactor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static bool isDryRun()
 * @method static \ArielMejiaDev\HealingFactor\HealingFactorManager manager()
 * @method static \ArielMejiaDev\HealingFactor\HealingFactor auth(\Closure $callback)
 * @method static bool check(mixed $user)
 *
 * @see \ArielMejiaDev\HealingFactor\HealingFactor
 */
class HealingFactor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ArielMejiaDev\HealingFactor\HealingFactor::class;
    }
}

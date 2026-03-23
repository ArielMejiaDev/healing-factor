<?php

namespace ArielMejiaDev\XFactor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static bool isDryRun()
 * @method static \ArielMejiaDev\XFactor\XFactorManager manager()
 * @method static \ArielMejiaDev\XFactor\XFactor auth(\Closure $callback)
 * @method static bool check(mixed $user)
 *
 * @see \ArielMejiaDev\XFactor\XFactor
 */
class XFactor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ArielMejiaDev\XFactor\XFactor::class;
    }
}

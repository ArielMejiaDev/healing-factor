<?php

namespace ArielMejiaDev\XFactor;

class XFactor
{
    protected static ?\Closure $authUsing = null;

    public function __construct(
        protected XFactorManager $manager,
    ) {}

    public function auth(\Closure $callback): static
    {
        static::$authUsing = $callback;

        return $this;
    }

    public static function check(mixed $user): bool
    {
        return (static::$authUsing ?? fn () => app()->environment('local'))($user);
    }

    public function isEnabled(): bool
    {
        if (! config('x-factor.enabled', true)) {
            return false;
        }

        $environments = config('x-factor.environments', []);
        if (! empty($environments) && ! in_array(app()->environment(), $environments, true)) {
            return false;
        }

        return true;
    }

    public function isDryRun(): bool
    {
        return (bool) config('x-factor.dry_run', false);
    }

    public function manager(): XFactorManager
    {
        return $this->manager;
    }
}

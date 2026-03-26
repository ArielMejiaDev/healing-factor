<?php

namespace ArielMejiaDev\HealingFactor;

class HealingFactor
{
    protected static ?\Closure $authUsing = null;

    public function __construct(
        protected HealingFactorManager $manager,
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
        if (! config('healing-factor.enabled', true)) {
            return false;
        }

        $environments = config('healing-factor.environments', []);
        if (! empty($environments) && ! in_array(app()->environment(), $environments, true)) {
            return false;
        }

        return true;
    }

    public function isDryRun(): bool
    {
        return (bool) config('healing-factor.dry_run', false);
    }

    public function manager(): HealingFactorManager
    {
        return $this->manager;
    }
}

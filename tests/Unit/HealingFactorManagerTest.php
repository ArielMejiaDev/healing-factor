<?php

use ArielMejiaDev\HealingFactor\Drivers\ApiDriver;
use ArielMejiaDev\HealingFactor\Drivers\CLIDriver;
use ArielMejiaDev\HealingFactor\HealingFactorManager;

it('returns CLI driver by default', function () {
    $manager = app(HealingFactorManager::class);

    expect($manager->driver())->toBeInstanceOf(CLIDriver::class);
});

it('returns API driver when configured', function () {
    config()->set('healing-factor.driver', 'api');

    $manager = app(HealingFactorManager::class);

    expect($manager->driver())->toBeInstanceOf(ApiDriver::class);
});

it('returns a category-specific driver', function () {
    config()->set('healing-factor.categories.quick_fixes.cli_tool', 'claude');
    config()->set('healing-factor.categories.quick_fixes.timeout', 1800);

    $manager = app(HealingFactorManager::class);

    $driver = $manager->driverForCategory('quick_fixes');

    expect($driver)->toBeInstanceOf(CLIDriver::class);
});

it('returns the default driver when category is null', function () {
    $manager = app(HealingFactorManager::class);

    $driver = $manager->driverForCategory(null);

    expect($driver)->toBeInstanceOf(CLIDriver::class);
});

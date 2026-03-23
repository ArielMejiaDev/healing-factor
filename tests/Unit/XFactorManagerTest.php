<?php

use ArielMejiaDev\XFactor\Drivers\ApiDriver;
use ArielMejiaDev\XFactor\Drivers\CLIDriver;
use ArielMejiaDev\XFactor\XFactorManager;

it('returns CLI driver by default', function () {
    $manager = app(XFactorManager::class);

    expect($manager->driver())->toBeInstanceOf(CLIDriver::class);
});

it('returns API driver when configured', function () {
    config()->set('x-factor.driver', 'api');

    $manager = app(XFactorManager::class);

    expect($manager->driver())->toBeInstanceOf(ApiDriver::class);
});

it('returns a category-specific driver', function () {
    config()->set('x-factor.categories.quick_fixes.cli_tool', 'claude');
    config()->set('x-factor.categories.quick_fixes.timeout', 1800);

    $manager = app(XFactorManager::class);

    $driver = $manager->driverForCategory('quick_fixes');

    expect($driver)->toBeInstanceOf(CLIDriver::class);
});

it('returns the default driver when category is null', function () {
    $manager = app(XFactorManager::class);

    $driver = $manager->driverForCategory(null);

    expect($driver)->toBeInstanceOf(CLIDriver::class);
});

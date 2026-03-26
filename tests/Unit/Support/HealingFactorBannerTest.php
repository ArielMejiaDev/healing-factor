<?php

use ArielMejiaDev\HealingFactor\Support\HealingFactorBanner;

it('renders 6 lines of output', function () {
    $lines = HealingFactorBanner::render('aurora');

    expect($lines)->toHaveCount(6);
});

it('includes ANSI color codes in output', function () {
    $lines = HealingFactorBanner::render('ocean');

    $joined = implode("\n", $lines);
    expect($joined)->toContain("\e[38;5;");
});

it('accepts a specific gradient', function () {
    $a = implode('', HealingFactorBanner::render('ember'));
    $b = implode('', HealingFactorBanner::render('ember'));

    expect($a)->toBe($b);
});

it('uses different gradients on random renders', function () {
    $results = collect(range(1, 20))
        ->map(fn () => implode('', HealingFactorBanner::render()))
        ->unique();

    expect($results->count())->toBeGreaterThan(1);
});

it('contains unicode block characters for HEALING-FACTOR', function () {
    $lines = HealingFactorBanner::render('aurora');
    $joined = implode("\n", $lines);

    expect($joined)->toContain('██');
    expect($joined)->toContain('╔');
    expect($joined)->toContain('╗');
});

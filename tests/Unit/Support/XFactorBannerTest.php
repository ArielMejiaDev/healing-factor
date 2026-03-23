<?php

use ArielMejiaDev\XFactor\Support\XFactorBanner;

it('renders 6 lines of output', function () {
    $lines = XFactorBanner::render('aurora');

    expect($lines)->toHaveCount(6);
});

it('includes ANSI color codes in output', function () {
    $lines = XFactorBanner::render('ocean');

    $joined = implode("\n", $lines);
    expect($joined)->toContain("\e[38;5;");
});

it('accepts a specific gradient', function () {
    $a = implode('', XFactorBanner::render('ember'));
    $b = implode('', XFactorBanner::render('ember'));

    expect($a)->toBe($b);
});

it('uses different gradients on random renders', function () {
    $results = collect(range(1, 20))
        ->map(fn () => implode('', XFactorBanner::render()))
        ->unique();

    expect($results->count())->toBeGreaterThan(1);
});

it('contains unicode block characters for X-FACTOR', function () {
    $lines = XFactorBanner::render('aurora');
    $joined = implode("\n", $lines);

    expect($joined)->toContain('██');
    expect($joined)->toContain('╔');
    expect($joined)->toContain('╗');
});

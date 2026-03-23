<?php

use ArielMejiaDev\XFactor\Services\Debouncer;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->debouncer = new Debouncer;
    Cache::flush();
});

it('allows processing a new fingerprint', function () {
    expect($this->debouncer->shouldProcess('abc123'))->toBeTrue();
});

it('prevents processing the same fingerprint within debounce window', function () {
    $this->debouncer->shouldProcess('abc123');

    expect($this->debouncer->shouldProcess('abc123'))->toBeFalse();
});

it('allows different fingerprints', function () {
    $this->debouncer->shouldProcess('abc123');

    expect($this->debouncer->shouldProcess('def456'))->toBeTrue();
});

it('can clear a fingerprint debounce', function () {
    $this->debouncer->shouldProcess('abc123');
    $this->debouncer->clear('abc123');

    expect($this->debouncer->shouldProcess('abc123'))->toBeTrue();
});

it('uses the configured debounce minutes', function () {
    config()->set('x-factor.debounce_minutes', 10);

    $this->debouncer->shouldProcess('abc123');

    expect(Cache::has('x-factor:debounce:abc123'))->toBeTrue();
});

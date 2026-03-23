<?php

use ArielMejiaDev\XFactor\Services\FingerprintGenerator;

beforeEach(function () {
    $this->generator = new FingerprintGenerator;
});

it('generates a 64-character SHA-256 fingerprint', function () {
    $fingerprint = $this->generator->generate('ErrorException', 'Something went wrong');

    expect($fingerprint)->toBeString()->toHaveLength(64);
});

it('generates deterministic fingerprints', function () {
    $fp1 = $this->generator->generate('ErrorException', 'msg', '/app/Foo.php', 42);
    $fp2 = $this->generator->generate('ErrorException', 'msg', '/app/Foo.php', 42);

    expect($fp1)->toBe($fp2);
});

it('generates different fingerprints for different inputs', function () {
    $fp1 = $this->generator->generate('ErrorException', 'msg1');
    $fp2 = $this->generator->generate('ErrorException', 'msg2');

    expect($fp1)->not->toBe($fp2);
});

it('handles null values by filtering them out', function () {
    $fp1 = $this->generator->generate('ErrorException');
    $fp2 = $this->generator->generate('ErrorException', null, null, null);

    expect($fp1)->toBe($fp2);
});

it('generates fingerprints from title', function () {
    $fp = $this->generator->generateFromTitle('Something went wrong', 'nightwatch');

    expect($fp)->toBeString()->toHaveLength(64);
});

it('generates different fingerprints for different sources', function () {
    $fp1 = $this->generator->generateFromTitle('Error', 'nightwatch');
    $fp2 = $this->generator->generateFromTitle('Error', 'bugsnag');

    expect($fp1)->not->toBe($fp2);
});

it('includes line number in fingerprint when provided', function () {
    $fp1 = $this->generator->generate('ErrorException', 'msg', '/app/Foo.php', 42);
    $fp2 = $this->generator->generate('ErrorException', 'msg', '/app/Foo.php', 43);

    expect($fp1)->not->toBe($fp2);
});

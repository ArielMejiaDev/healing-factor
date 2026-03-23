<?php

use ArielMejiaDev\XFactor\Monitors\BugsnagMonitor;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->monitor = new BugsnagMonitor;
});

it('processes exception trigger', function () {
    $request = Request::create('/webhook', 'POST', ['trigger' => 'exception']);

    expect($this->monitor->shouldProcess($request))->toBeTrue();
});

it('processes firstException trigger', function () {
    $request = Request::create('/webhook', 'POST', ['trigger' => 'firstException']);

    expect($this->monitor->shouldProcess($request))->toBeTrue();
});

it('ignores other triggers', function () {
    $request = Request::create('/webhook', 'POST', ['trigger' => 'projectSpiking']);

    expect($this->monitor->shouldProcess($request))->toBeFalse();
});

it('parses a valid bugsnag webhook payload', function () {
    $request = Request::create('/webhook', 'POST', [
        'trigger' => 'exception',
        'error' => [
            'exceptionClass' => 'RuntimeException',
            'message' => 'Something went wrong',
            'stackTrace' => 'Stack trace text here',
        ],
    ]);

    $data = $this->monitor->parseWebhook($request);

    expect($data)->not->toBeNull()
        ->and($data['source'])->toBe('bugsnag')
        ->and($data['title'])->toBe('RuntimeException: Something went wrong')
        ->and($data['exception_class'])->toBe('RuntimeException')
        ->and($data['exception_message'])->toBe('Something went wrong');
});

it('returns null for empty error data', function () {
    $request = Request::create('/webhook', 'POST', ['trigger' => 'exception']);

    expect($this->monitor->parseWebhook($request))->toBeNull();
});

it('verifies bearer token authorization', function () {
    $secret = 'my-bugsnag-token';

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_AUTHORIZATION' => "Bearer {$secret}",
    ]);

    expect($this->monitor->verifySignature($request, $secret))->toBeTrue();
});

it('rejects invalid authorization', function () {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer wrong-token',
    ]);

    expect($this->monitor->verifySignature($request, 'correct-token'))->toBeFalse();
});

it('rejects missing authorization', function () {
    $request = Request::create('/webhook', 'POST');

    expect($this->monitor->verifySignature($request, 'some-token'))->toBeFalse();
});

it('abbreviates array stack traces', function () {
    $request = Request::create('/webhook', 'POST', [
        'trigger' => 'exception',
        'error' => [
            'exceptionClass' => 'Error',
            'message' => 'test',
            'stackTrace' => [
                ['file' => '/app/Foo.php', 'lineNumber' => 42, 'method' => 'bar'],
                ['file' => '/app/Baz.php', 'lineNumber' => 10, 'method' => 'qux'],
            ],
        ],
    ]);

    $data = $this->monitor->parseWebhook($request);

    expect($data['stacktrace'])->toContain('/app/Foo.php:42 in bar')
        ->toContain('/app/Baz.php:10 in qux');
});

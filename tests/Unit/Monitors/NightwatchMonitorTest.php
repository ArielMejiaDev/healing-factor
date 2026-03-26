<?php

use ArielMejiaDev\HealingFactor\Monitors\NightwatchMonitor;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->monitor = new NightwatchMonitor;
});

it('processes issue.opened events', function () {
    $request = Request::create('/webhook', 'POST', ['event' => 'issue.opened']);

    expect($this->monitor->shouldProcess($request))->toBeTrue();
});

it('processes issue.reopened events', function () {
    $request = Request::create('/webhook', 'POST', ['event' => 'issue.reopened']);

    expect($this->monitor->shouldProcess($request))->toBeTrue();
});

it('ignores other events', function () {
    $request = Request::create('/webhook', 'POST', ['event' => 'issue.resolved']);

    expect($this->monitor->shouldProcess($request))->toBeFalse();
});

it('parses a real nightwatch webhook payload', function () {
    $request = Request::create('/webhook', 'POST', [
        'event' => 'issue.opened',
        'payload' => [
            'organization_id' => 'org-123',
            'application_id' => 'app-456',
            'environment' => ['id' => 'env-789'],
            'issue' => [
                'title' => 'ErrorException: Undefined variable',
                'details' => [
                    'class' => 'ErrorException',
                    'message' => 'Undefined variable $foo',
                    'file' => '/app/Http/Controllers/HomeController.php',
                    'line' => 42,
                ],
            ],
        ],
    ]);

    $data = $this->monitor->parseWebhook($request);

    expect($data)->not->toBeNull()
        ->and($data['source'])->toBe('nightwatch')
        ->and($data['organization_id'])->toBe('org-123')
        ->and($data['application_id'])->toBe('app-456')
        ->and($data['environment_id'])->toBe('env-789')
        ->and($data['title'])->toBe('ErrorException: Undefined variable')
        ->and($data['exception_class'])->toBe('ErrorException')
        ->and($data['exception_message'])->toBe('Undefined variable $foo')
        ->and($data['stacktrace'])->toBe('#0 /app/Http/Controllers/HomeController.php(42)');
});

it('parses legacy payload format for backward compatibility', function () {
    $request = Request::create('/webhook', 'POST', [
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'ErrorException: Undefined variable',
                'exception_class' => 'ErrorException',
                'message' => 'Undefined variable $foo',
                'stacktrace' => '#0 /app/Http/Controllers...',
            ],
        ],
    ]);

    $data = $this->monitor->parseWebhook($request);

    expect($data)->not->toBeNull()
        ->and($data['exception_class'])->toBe('ErrorException')
        ->and($data['exception_message'])->toBe('Undefined variable $foo')
        ->and($data['stacktrace'])->toBe('#0 /app/Http/Controllers...');
});

it('returns null for empty payload', function () {
    $request = Request::create('/webhook', 'POST', ['event' => 'issue.opened']);

    expect($this->monitor->parseWebhook($request))->toBeNull();
});

it('verifies HMAC signature with Nightwatch-Signature header', function () {
    $secret = 'test-secret';
    $body = json_encode(['event' => 'issue.opened', 'payload' => ['issue' => ['title' => 'test']]]);
    $signature = hash_hmac('sha256', $body, $secret);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_NIGHTWATCH_SIGNATURE' => $signature,
    ], $body);

    expect($this->monitor->verifySignature($request, $secret))->toBeTrue();
});

it('verifies HMAC signature with legacy X-Nightwatch-Signature header', function () {
    $secret = 'test-secret';
    $body = json_encode(['event' => 'issue.opened', 'payload' => ['issue' => ['title' => 'test']]]);
    $signature = hash_hmac('sha256', $body, $secret);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_NIGHTWATCH_SIGNATURE' => $signature,
    ], $body);

    expect($this->monitor->verifySignature($request, $secret))->toBeTrue();
});

it('rejects invalid signature', function () {
    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_NIGHTWATCH_SIGNATURE' => 'invalid-signature',
    ], '{"test": true}');

    expect($this->monitor->verifySignature($request, 'test-secret'))->toBeFalse();
});

it('rejects missing signature', function () {
    $request = Request::create('/webhook', 'POST', [], [], [], [], '{"test": true}');

    expect($this->monitor->verifySignature($request, 'test-secret'))->toBeFalse();
});

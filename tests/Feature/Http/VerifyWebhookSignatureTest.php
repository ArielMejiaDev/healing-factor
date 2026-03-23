<?php

use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('allows requests when no secret is configured', function () {
    config()->set('x-factor.webhook.secret', null);

    $this->postJson('/x-factor/webhook', [
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'Test issue',
                'exception_class' => 'ErrorException',
                'message' => 'test',
            ],
        ],
    ])->assertStatus(201);
});

it('rejects requests with invalid signature', function () {
    config()->set('x-factor.webhook.secret', 'test-secret');

    $this->postJson('/x-factor/webhook', [
        'event' => 'issue.opened',
        'payload' => ['issue' => ['title' => 'Test']],
    ], [
        'X-Nightwatch-Signature' => 'invalid-signature',
    ])->assertStatus(403);
});

it('accepts requests with valid HMAC signature', function () {
    $secret = 'test-secret';
    config()->set('x-factor.webhook.secret', $secret);

    $payload = json_encode([
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'Valid signature test',
                'exception_class' => 'ErrorException',
                'message' => 'test',
            ],
        ],
    ]);
    $signature = hash_hmac('sha256', $payload, $secret);

    $this->call('POST', '/x-factor/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_NIGHTWATCH_SIGNATURE' => $signature,
    ], $payload)->assertStatus(201);
});

it('rejects requests with missing signature when secret is configured', function () {
    config()->set('x-factor.webhook.secret', 'test-secret');

    $this->postJson('/x-factor/webhook', [
        'event' => 'issue.opened',
    ])->assertStatus(403);
});

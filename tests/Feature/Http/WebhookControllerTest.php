<?php

use ArielMejiaDev\HealingFactor\Events\IssueCreated;
use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Event::fake();
    config()->set('healing-factor.webhook.secret', null); // disable signature verification for tests
});

it('creates an issue from a valid nightwatch webhook', function () {
    $payload = [
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
    ];

    $this->postJson('/healing-factor/webhook', $payload)
        ->assertStatus(201)
        ->assertJson(['message' => 'Issue created.']);

    expect(Issue::count())->toBe(1);

    $issue = Issue::first();
    expect($issue->title)->toBe('ErrorException: Undefined variable');
    expect($issue->exception_class)->toBe('ErrorException');
    expect($issue->exception_message)->toBe('Undefined variable $foo');

    Queue::assertPushed(ResolveIssue::class);
    Event::assertDispatched(IssueCreated::class);
});

it('ignores non-processable events', function () {
    $this->postJson('/healing-factor/webhook', ['event' => 'issue.resolved'])
        ->assertStatus(200)
        ->assertJson(['message' => 'Event ignored.']);

    expect(Issue::count())->toBe(0);
});

it('returns 422 when payload cannot be parsed', function () {
    $this->postJson('/healing-factor/webhook', ['event' => 'issue.opened'])
        ->assertStatus(422);
});

it('returns 200 when healing-factor is disabled', function () {
    config()->set('healing-factor.enabled', false);

    $this->postJson('/healing-factor/webhook', ['event' => 'issue.opened'])
        ->assertStatus(200)
        ->assertJson(['message' => 'Healing-Factor is disabled.']);
});

it('deduplicates issues with the same fingerprint', function () {
    $payload = [
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'ErrorException: test',
                'details' => [
                    'class' => 'ErrorException',
                    'message' => 'test message',
                ],
            ],
        ],
    ];

    // First request creates the issue
    $this->postJson('/healing-factor/webhook', $payload)->assertStatus(201);

    // Clear debounce so second request isn't blocked by debounce
    Cache::flush();

    // Second request is deduplicated
    $this->postJson('/healing-factor/webhook', $payload)->assertStatus(200)
        ->assertJson(['message' => 'Issue already being processed.']);

    expect(Issue::count())->toBe(1);
});

it('ignores configured exception classes', function () {
    $payload = [
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'ThrottleRequestsException',
                'details' => [
                    'class' => ThrottleRequestsException::class,
                    'message' => 'Too many requests',
                ],
            ],
        ],
    ];

    $this->postJson('/healing-factor/webhook', $payload)
        ->assertStatus(200)
        ->assertJson(['message' => 'Exception ignored.']);
});

it('ignores exceptions matching ignored message patterns', function () {
    $payload = [
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'ErrorException: __VSCODE_LARAVEL_STARTUP_ERROR__: Undefined variable $user',
                'details' => [
                    'class' => 'ErrorException',
                    'message' => '__VSCODE_LARAVEL_STARTUP_ERROR__: Undefined variable $user',
                ],
            ],
        ],
    ];

    $this->postJson('/healing-factor/webhook', $payload)
        ->assertStatus(200)
        ->assertJson(['message' => 'Message pattern ignored.']);

    expect(Issue::count())->toBe(0);
});

it('debounces duplicate requests', function () {
    $payload = [
        'event' => 'issue.opened',
        'payload' => [
            'issue' => [
                'title' => 'ErrorException: debounce test',
                'details' => [
                    'class' => 'ErrorException',
                    'message' => 'debounce test',
                ],
            ],
        ],
    ];

    $this->postJson('/healing-factor/webhook', $payload)->assertStatus(201);

    // Second request within debounce window
    $this->postJson('/healing-factor/webhook', $payload)
        ->assertStatus(200)
        ->assertJson(['message' => 'Debounced.']);
});

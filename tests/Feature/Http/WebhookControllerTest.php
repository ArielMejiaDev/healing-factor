<?php

use ArielMejiaDev\XFactor\Events\IssueCreated;
use ArielMejiaDev\XFactor\Jobs\ResolveIssue;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Event::fake();
    config()->set('x-factor.webhook.secret', null); // disable signature verification for tests
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

    $this->postJson('/x-factor/webhook', $payload)
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
    $this->postJson('/x-factor/webhook', ['event' => 'issue.resolved'])
        ->assertStatus(200)
        ->assertJson(['message' => 'Event ignored.']);

    expect(Issue::count())->toBe(0);
});

it('returns 422 when payload cannot be parsed', function () {
    $this->postJson('/x-factor/webhook', ['event' => 'issue.opened'])
        ->assertStatus(422);
});

it('returns 200 when x-factor is disabled', function () {
    config()->set('x-factor.enabled', false);

    $this->postJson('/x-factor/webhook', ['event' => 'issue.opened'])
        ->assertStatus(200)
        ->assertJson(['message' => 'X-Factor is disabled.']);
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
    $this->postJson('/x-factor/webhook', $payload)->assertStatus(201);

    // Clear debounce so second request isn't blocked by debounce
    Cache::flush();

    // Second request is deduplicated
    $this->postJson('/x-factor/webhook', $payload)->assertStatus(200)
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

    $this->postJson('/x-factor/webhook', $payload)
        ->assertStatus(200)
        ->assertJson(['message' => 'Exception ignored.']);
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

    $this->postJson('/x-factor/webhook', $payload)->assertStatus(201);

    // Second request within debounce window
    $this->postJson('/x-factor/webhook', $payload)
        ->assertStatus(200)
        ->assertJson(['message' => 'Debounced.']);
});

<?php

use ArielMejiaDev\XFactor\Events\IssueCreated;
use ArielMejiaDev\XFactor\Jobs\ResolveIssue;
use ArielMejiaDev\XFactor\Listeners\ExceptionListener;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Queue::fake();
    Event::fake([IssueCreated::class]);
});

it('creates an issue from an error-level logged exception', function () {
    $listener = app(ExceptionListener::class);

    $exception = new ErrorException('Test error');

    $event = new MessageLogged('error', 'Test error', ['exception' => $exception]);

    $listener->handleMessageLogged($event);

    expect(Issue::count())->toBe(1);
    expect(Issue::first()->exception_class)->toBe('ErrorException');

    Queue::assertPushed(ResolveIssue::class);
    Event::assertDispatched(IssueCreated::class);
});

it('ignores non-error log levels', function () {
    $listener = app(ExceptionListener::class);

    $event = new MessageLogged('info', 'Just info', ['exception' => new Exception('test')]);

    $listener->handleMessageLogged($event);

    expect(Issue::count())->toBe(0);
});

it('ignores log events without exceptions', function () {
    $listener = app(ExceptionListener::class);

    $event = new MessageLogged('error', 'No exception', []);

    $listener->handleMessageLogged($event);

    expect(Issue::count())->toBe(0);
});

it('skips ignored exceptions', function () {
    $listener = app(ExceptionListener::class);

    $exception = new HttpException(500, 'Server Error');

    $event = new MessageLogged('error', 'Server Error', ['exception' => $exception]);

    $listener->handleMessageLogged($event);

    expect(Issue::count())->toBe(0);
});

it('deduplicates exceptions with the same fingerprint', function () {
    $listener = app(ExceptionListener::class);

    $exception = new ErrorException('Same error', 0, 1, '/app/Foo.php', 42);

    $event1 = new MessageLogged('error', 'Same error', ['exception' => $exception]);
    $listener->handleMessageLogged($event1);

    // Clear debounce to test DB-level dedup
    Cache::flush();

    $event2 = new MessageLogged('error', 'Same error', ['exception' => $exception]);
    $listener->handleMessageLogged($event2);

    expect(Issue::count())->toBe(1);
});

it('truncates the title to 255 characters for long exception messages', function () {
    config()->set('cache.default', 'array');

    $listener = app(ExceptionListener::class);

    $longMessage = str_repeat('a', 300);
    $exception = new ErrorException($longMessage);

    $event = new MessageLogged('error', $longMessage, ['exception' => $exception]);

    $listener->handleMessageLogged($event);

    expect(Issue::count())->toBe(1);
    expect(mb_strlen(Issue::first()->title))->toBeLessThanOrEqual(255);
});

it('does not process when x-factor is disabled', function () {
    config()->set('x-factor.enabled', false);

    $listener = app(ExceptionListener::class);

    $exception = new ErrorException('Test');
    $event = new MessageLogged('error', 'Test', ['exception' => $exception]);

    $listener->handleMessageLogged($event);

    expect(Issue::count())->toBe(0);
});

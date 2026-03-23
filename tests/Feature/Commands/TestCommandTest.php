<?php

use ArielMejiaDev\XFactor\Jobs\ResolveIssue;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

it('creates a test issue and dispatches it', function () {
    Queue::fake();

    $this->artisan('x-factor:test')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(1);
    expect(Issue::first()->source)->toBe('test');

    Queue::assertPushed(ResolveIssue::class);
});

it('creates a test issue with custom exception class', function () {
    Queue::fake();

    $this->artisan('x-factor:test --exception=RuntimeException')
        ->assertExitCode(0);

    expect(Issue::first()->exception_class)->toBe('RuntimeException');
});

it('can run synchronously', function () {
    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $this->artisan('x-factor:test --sync')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(1);
});

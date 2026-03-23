<?php

use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Events\IssueResolutionFailed;
use ArielMejiaDev\XFactor\Events\IssueResolved;
use ArielMejiaDev\XFactor\Events\IssueResolving;
use ArielMejiaDev\XFactor\Models\Issue;
use ArielMejiaDev\XFactor\Services\IssueResolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    Event::fake();
    Process::fake(fn () => Process::result(output: '{"result": "success"}', exitCode: 0));
});

it('resolves a pending issue successfully', function () {
    $issue = Issue::factory()->pending()->create(['exception_class' => ErrorException::class]);
    $resolver = app(IssueResolver::class);

    $result = $resolver->resolve($issue);

    expect($result)->toBeTrue();

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Resolved);
    expect($issue->attempts)->toBe(1);

    Event::assertDispatched(IssueResolving::class);
    Event::assertDispatched(IssueResolved::class);
});

it('skips already-resolving issues', function () {
    $issue = Issue::factory()->resolving()->create();
    $resolver = app(IssueResolver::class);

    $result = $resolver->resolve($issue);

    expect($result)->toBeFalse();
    Event::assertNotDispatched(IssueResolving::class);
});

it('marks issue as failed on CLI failure', function () {
    Process::fake(fn () => Process::result(output: '', errorOutput: 'Process failed', exitCode: 1));

    $issue = Issue::factory()->pending()->create();
    $resolver = app(IssueResolver::class);

    $result = $resolver->resolve($issue);

    expect($result)->toBeFalse();

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Failed);

    Event::assertDispatched(IssueResolutionFailed::class);
});

it('detects quick_fixes category for ErrorException', function () {
    $issue = Issue::factory()->create(['exception_class' => ErrorException::class]);
    $resolver = app(IssueResolver::class);

    $category = $resolver->detectCategory($issue);

    expect($category)->toBe('quick_fixes');
});

it('detects complex_fixes category for RuntimeException', function () {
    $issue = Issue::factory()->create(['exception_class' => RuntimeException::class]);
    $resolver = app(IssueResolver::class);

    $category = $resolver->detectCategory($issue);

    expect($category)->toBe('complex_fixes');
});

it('returns null category for unknown exception', function () {
    $issue = Issue::factory()->create(['exception_class' => 'App\\Custom\\Exception']);
    $resolver = app(IssueResolver::class);

    $category = $resolver->detectCategory($issue);

    expect($category)->toBeNull();
});

it('handles dry run mode', function () {
    config()->set('x-factor.dry_run', true);

    $issue = Issue::factory()->pending()->create();
    $resolver = app(IssueResolver::class);

    $result = $resolver->resolve($issue);

    expect($result)->toBeTrue();

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Pending);

    Process::assertNothingRan();
});

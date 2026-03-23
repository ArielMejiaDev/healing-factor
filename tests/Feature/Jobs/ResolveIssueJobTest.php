<?php

use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Jobs\ResolveIssue;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;

it('resolves an issue via the job', function () {
    Event::fake();
    Process::fake(fn () => Process::result(output: '{"result": "fixed"}', exitCode: 0));

    $issue = Issue::factory()->pending()->create();

    ResolveIssue::dispatchSync($issue);

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Resolved);
});

it('has a unique ID based on issue ID', function () {
    $issue = Issue::factory()->create();
    $job = new ResolveIssue($issue);

    expect($job->uniqueId())->toBe("x-factor-resolve-{$issue->id}");
});

it('uses configured queue and connection', function () {
    config()->set('x-factor.queue.name', 'x-factor-queue');
    config()->set('x-factor.queue.connection', 'redis');

    $issue = Issue::factory()->create();
    $job = new ResolveIssue($issue);

    expect($job->queue)->toBe('x-factor-queue');
    expect($job->connection)->toBe('redis');
});

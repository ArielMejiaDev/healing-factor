<?php

use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
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

    expect($job->uniqueId())->toBe("healing-factor-resolve-{$issue->id}");
});

it('uses configured queue and connection', function () {
    config()->set('healing-factor.queue.name', 'healing-factor-queue');
    config()->set('healing-factor.queue.connection', 'redis');

    $issue = Issue::factory()->create();
    $job = new ResolveIssue($issue);

    expect($job->queue)->toBe('healing-factor-queue');
    expect($job->connection)->toBe('redis');
});

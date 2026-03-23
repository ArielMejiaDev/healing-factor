<?php

use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Jobs\ResolveIssue;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('retries a failed issue', function () {
    $issue = Issue::factory()->failed()->create();

    $this->artisan("x-factor:retry {$issue->id}")
        ->assertExitCode(0);

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Pending);
    expect($issue->failure_reason)->toBeNull();

    Queue::assertPushed(ResolveIssue::class);
});

it('fails when issue does not exist', function () {
    $this->artisan('x-factor:retry 999')
        ->expectsOutput('Issue not found.')
        ->assertExitCode(1);
});

it('fails when issue is not in failed status', function () {
    $issue = Issue::factory()->pending()->create();

    $this->artisan("x-factor:retry {$issue->id}")
        ->assertExitCode(1);
});

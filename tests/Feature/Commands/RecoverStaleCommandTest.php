<?php

use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use ArielMejiaDev\HealingFactor\Models\Issue;

it('marks stale resolving issues as failed', function () {
    config()->set('healing-factor.process.timeout', 3600); // 60 min

    // Stale: updated 80 minutes ago (beyond 60 min timeout + 10 min buffer)
    $stale = Issue::factory()->resolving()->create(['updated_at' => now()->subMinutes(80)]);

    // Recent: updated 5 minutes ago (within timeout)
    $recent = Issue::factory()->resolving()->create(['updated_at' => now()->subMinutes(5)]);

    $this->artisan('healing-factor:recover-stale')
        ->assertExitCode(0);

    $stale->refresh();
    $recent->refresh();

    expect($stale->status)->toBe(IssueStatus::Failed);
    expect($stale->failure_reason)->toContain('timed out');
    expect($stale->failed_at)->not->toBeNull();

    expect($recent->status)->toBe(IssueStatus::Resolving);
});

it('reports when no stale issues are found', function () {
    $this->artisan('healing-factor:recover-stale')
        ->expectsOutput('No stale resolving issues found.')
        ->assertExitCode(0);
});

it('accepts a custom minutes threshold', function () {
    $issue = Issue::factory()->resolving()->create(['updated_at' => now()->subMinutes(20)]);

    $this->artisan('healing-factor:recover-stale --minutes=15')
        ->assertExitCode(0);

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Failed);
});

it('does not affect pending or resolved issues', function () {
    Issue::factory()->pending()->create(['updated_at' => now()->subDays(1)]);
    Issue::factory()->resolved()->create(['updated_at' => now()->subDays(1)]);

    $this->artisan('healing-factor:recover-stale --minutes=1')
        ->expectsOutput('No stale resolving issues found.')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(2);
});

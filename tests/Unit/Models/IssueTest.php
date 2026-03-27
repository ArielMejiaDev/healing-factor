<?php

use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Support\Carbon;

it('can create an issue with factory', function () {
    $issue = Issue::factory()->create();

    expect($issue)->toBeInstanceOf(Issue::class);
    expect($issue->status)->toBe(IssueStatus::Pending);
    expect($issue->fingerprint)->toBeString()->toHaveLength(64);
});

it('casts status to IssueStatus enum', function () {
    $issue = Issue::factory()->create();

    expect($issue->status)->toBeInstanceOf(IssueStatus::class);
});

it('casts payload to array', function () {
    $issue = Issue::factory()->create(['payload' => ['key' => 'value']]);

    expect($issue->payload)->toBeArray()->toBe(['key' => 'value']);
});

it('casts resolved_at and failed_at to datetime', function () {
    $issue = Issue::factory()->resolved()->create();

    expect($issue->resolved_at)->toBeInstanceOf(Carbon::class);
});

it('can transition from pending to resolving', function () {
    $issue = Issue::factory()->pending()->create();

    expect($issue->markResolving())->toBeTrue();

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Resolving);
});

it('prevents double transition to resolving', function () {
    $issue = Issue::factory()->pending()->create();

    expect($issue->markResolving())->toBeTrue();
    expect($issue->markResolving())->toBeFalse();
});

it('can only mark resolving from pending status', function () {
    $issue = Issue::factory()->resolving()->create();

    expect($issue->markResolving())->toBeFalse();
});

it('can transition from resolving to resolved', function () {
    $issue = Issue::factory()->resolving()->create();

    expect($issue->markResolved('https://github.com/pr/1'))->toBeTrue();

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Resolved);
    expect($issue->pr_url)->toBe('https://github.com/pr/1');
    expect($issue->resolved_at)->not->toBeNull();
});

it('can transition from resolving to failed', function () {
    $issue = Issue::factory()->resolving()->create();

    expect($issue->markFailed('CLI process timed out'))->toBeTrue();

    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Failed);
    expect($issue->failure_reason)->toBe('CLI process timed out');
    expect($issue->failed_at)->not->toBeNull();
});

it('cannot mark resolved from pending', function () {
    $issue = Issue::factory()->pending()->create();

    expect($issue->markResolved())->toBeFalse();
});

it('can mark failed from pending', function () {
    $issue = Issue::factory()->pending()->create();

    expect($issue->markFailed('some reason'))->toBeTrue();
    $issue->refresh();
    expect($issue->status)->toBe(IssueStatus::Failed);
    expect($issue->failure_reason)->toBe('some reason');
});

it('increments attempts', function () {
    $issue = Issue::factory()->create(['attempts' => 0]);

    $issue->incrementAttempts();

    $issue->refresh();
    expect($issue->attempts)->toBe(1);
});

it('scopes pending issues', function () {
    Issue::factory()->pending()->count(2)->create();
    Issue::factory()->resolved()->create();

    expect(Issue::pending()->count())->toBe(2);
});

it('scopes resolving issues', function () {
    Issue::factory()->resolving()->count(3)->create();
    Issue::factory()->pending()->create();

    expect(Issue::resolving()->count())->toBe(3);
});

it('scopes resolved issues', function () {
    Issue::factory()->resolved()->count(2)->create();
    Issue::factory()->failed()->create();

    expect(Issue::resolved()->count())->toBe(2);
});

it('scopes failed issues', function () {
    Issue::factory()->failed()->count(2)->create();
    Issue::factory()->resolved()->create();

    expect(Issue::failed()->count())->toBe(2);
});

it('scopes stale issues', function () {
    // Old resolved issue
    Issue::factory()->resolved()->create([
        'updated_at' => now()->subDays(31),
    ]);
    // Recent resolved issue
    Issue::factory()->resolved()->create([
        'updated_at' => now()->subDays(5),
    ]);
    // Old pending issue (should NOT be included - not terminal)
    Issue::factory()->pending()->create([
        'updated_at' => now()->subDays(31),
    ]);

    expect(Issue::stale(30)->count())->toBe(1);
});

it('checks isPending helper', function () {
    $issue = Issue::factory()->pending()->create();
    expect($issue->isPending())->toBeTrue();

    $issue = Issue::factory()->resolving()->create();
    expect($issue->isPending())->toBeFalse();
});

it('checks isResolving helper', function () {
    $issue = Issue::factory()->resolving()->create();
    expect($issue->isResolving())->toBeTrue();

    $issue = Issue::factory()->pending()->create();
    expect($issue->isResolving())->toBeFalse();
});

it('uses the healing_factor_issues table', function () {
    $issue = new Issue;

    expect($issue->getTable())->toBe('healing_factor_issues');
});

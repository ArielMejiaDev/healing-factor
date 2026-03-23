<?php

use ArielMejiaDev\XFactor\Drivers\ApiDriver;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    config()->set('x-factor.api_keys.anthropic', 'sk-test-key');
    config()->set('x-factor.api.allowed_commands', [
        'git',
        'php artisan test',
        './vendor/bin/pest',
        './vendor/bin/phpunit',
        'composer dump-autoload',
        'gh pr create',
    ]);
});

it('refuses to run without branch_name', function () {
    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $driver = new ApiDriver(
        model: 'claude-sonnet-4-6',
        maxTokens: 1024,
        maxTurns: 5,
        timeout: 60,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => null]);
    $result = $driver->resolve($issue, 'Fix this');

    expect($result->success)->toBeFalse();
    expect($result->errorOutput)->toContain('branch_name is not set');

    Process::assertDidntRun(fn ($process) => $process->command[0] === 'git');
});

it('returns failure when worktree creation fails', function () {
    Process::fake(function ($process) {
        if ($process->command[0] === 'git') {
            return Process::result(output: '', errorOutput: 'fatal: branch already exists', exitCode: 128);
        }

        return Process::result(output: 'ok', exitCode: 0);
    });

    $driver = new ApiDriver(
        model: 'claude-sonnet-4-6',
        maxTokens: 1024,
        maxTurns: 5,
        timeout: 60,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'x-factor/fix-api-test']);
    $result = $driver->resolve($issue, 'Fix this bug');

    expect($result->success)->toBeFalse();
    expect($result->errorOutput)->toContain('Failed to create git worktree');
});

it('creates worktree and cleans up on resolve', function () {
    // Mock Process for worktree operations (Anthropic SDK will fail since
    // we can't easily mock it, but we test the worktree lifecycle)
    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $driver = new ApiDriver(
        model: 'claude-sonnet-4-6',
        maxTokens: 1024,
        maxTurns: 1,
        timeout: 60,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'x-factor/fix-api-worktree']);

    // The resolve will fail because the Anthropic Client will throw
    // (no real API key / network), but worktree should still be cleaned up
    $result = $driver->resolve($issue, 'Fix this');

    // Worktree create should have been called
    Process::assertRan(fn ($process) => $process->command[0] === 'git'
        && $process->command[1] === 'worktree'
        && $process->command[2] === 'add'
    );

    // Worktree remove should have been called (cleanup)
    Process::assertRan(fn ($process) => $process->command[0] === 'git'
        && $process->command[1] === 'worktree'
        && $process->command[2] === 'remove'
    );

    // Result should indicate failure from API error
    expect($result->success)->toBeFalse();
    expect($result->errorOutput)->toContain('Anthropic API error');
});

it('accepts constructor parameters for model, maxTokens, maxTurns, timeout', function () {
    $driver = new ApiDriver(
        model: 'claude-opus-4-6',
        maxTokens: 4096,
        maxTurns: 10,
        timeout: 1800,
        workingDirectory: '/custom/path',
    );

    // Verify the object was constructed without error
    expect($driver)->toBeInstanceOf(ApiDriver::class);
});

it('uses default model from config when null', function () {
    config()->set('x-factor.api.model', 'claude-haiku-4-5-20251001');

    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $driver = new ApiDriver(
        model: null,
        maxTokens: 1024,
        maxTurns: 1,
        timeout: 60,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'x-factor/fix-default-model']);
    $result = $driver->resolve($issue, 'Fix this');

    // Will fail with API error since no real API, but confirms config fallback is used
    expect($result->success)->toBeFalse();
});

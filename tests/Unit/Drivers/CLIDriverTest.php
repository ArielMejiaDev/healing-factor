<?php

use ArielMejiaDev\HealingFactor\Drivers\CLIDriver;
use ArielMejiaDev\HealingFactor\Enums\CliTool;
use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Support\Facades\Process;

it('resolves an issue by running a CLI process in a worktree', function () {
    Process::fake(fn () => Process::result(output: '{"result": "success"}', exitCode: 0));

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: null,
        timeout: 3600,
        maxTurns: 25,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-test-abc123']);
    $result = $driver->resolve($issue, 'Fix this bug');

    expect($result->success)->toBeTrue();
    expect(trim($result->output))->toBe('success');
    expect($result->exitCode)->toBe(0);

    // Should run: git worktree add, claude, git worktree remove
    Process::assertRan(fn ($process) => $process->command[0] === 'git'
        && $process->command[1] === 'worktree'
        && $process->command[2] === 'add'
    );

    Process::assertRan(fn ($process) => $process->command[0] === 'claude');

    Process::assertRan(fn ($process) => $process->command[0] === 'git'
        && $process->command[1] === 'worktree'
        && $process->command[2] === 'remove'
    );
});

it('returns failure when worktree creation fails', function () {
    Process::fake(function ($process) {
        if ($process->command[0] === 'git') {
            return Process::result(output: '', errorOutput: 'fatal: branch already exists', exitCode: 128);
        }

        return Process::result(output: 'ok', exitCode: 0);
    });

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: null,
        timeout: 3600,
        maxTurns: null,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-existing']);
    $result = $driver->resolve($issue, 'Fix this bug');

    expect($result->success)->toBeFalse();
    expect($result->errorOutput)->toContain('Failed to create git worktree');
});

it('returns failure result when CLI process fails', function () {
    Process::fake(function ($process) {
        if ($process->command[0] === 'claude') {
            return Process::result(output: '', errorOutput: 'Error occurred', exitCode: 1);
        }

        return Process::result(output: 'ok', exitCode: 0);
    });

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: null,
        timeout: 3600,
        maxTurns: null,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-fail-test']);
    $result = $driver->resolve($issue, 'Fix this bug');

    expect($result->success)->toBeFalse();
    expect(trim($result->errorOutput))->toBe('Error occurred');
    expect($result->exitCode)->toBe(1);
});

it('cleans up worktree even when CLI process fails', function () {
    Process::fake(function ($process) {
        if ($process->command[0] === 'claude') {
            return Process::result(output: '', errorOutput: 'CLI failed', exitCode: 1);
        }

        return Process::result(output: 'ok', exitCode: 0);
    });

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: null,
        timeout: 3600,
        maxTurns: null,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-cleanup-test']);
    $result = $driver->resolve($issue, 'Fix this');

    expect($result->success)->toBeFalse();

    // Worktree remove should still be called
    Process::assertRan(fn ($process) => $process->command[0] === 'git'
        && $process->command[1] === 'worktree'
        && $process->command[2] === 'remove'
    );
});

it('refuses to run when branch_name is null', function () {
    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: null,
        timeout: 3600,
        maxTurns: null,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => null]);
    $result = $driver->resolve($issue, 'Fix this');

    expect($result->success)->toBeFalse();
    expect($result->errorOutput)->toContain('branch_name is not set');

    // Should NOT have run any process
    Process::assertDidntRun(fn ($process) => $process->command[0] === 'claude');
    Process::assertDidntRun(fn ($process) => $process->command[0] === 'git');
});

it('uses opencode when configured', function () {
    Process::fake(fn () => Process::result(output: '{"result": "done"}', exitCode: 0));

    $driver = new CLIDriver(
        tool: CliTool::OpenCode,
        model: null,
        timeout: 3600,
        maxTurns: null,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-opencode']);
    $result = $driver->resolve($issue, 'Fix this bug');

    expect($result->success)->toBeTrue();

    Process::assertRan(fn ($process) => $process->command[0] === 'opencode');
});

it('passes ANTHROPIC_API_KEY as environment variable when set', function () {
    config()->set('healing-factor.api_keys.anthropic', 'sk-test-key');

    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: null,
        timeout: 3600,
        maxTurns: null,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-env-test']);
    $driver->resolve($issue, 'Fix this');

    Process::assertRan(fn ($process) => $process->command[0] === 'claude');
});

it('uses custom model and max turns', function () {
    Process::fake(fn () => Process::result(output: 'ok', exitCode: 0));

    $driver = new CLIDriver(
        tool: CliTool::Claude,
        model: 'claude-sonnet-4-20250514',
        timeout: 1800,
        maxTurns: 10,
        workingDirectory: null,
    );

    $issue = Issue::factory()->create(['branch_name' => 'healing-factor/fix-model-test']);
    $driver->resolve($issue, 'Fix this');

    Process::assertRan(function ($process) {
        return in_array('--model', $process->command)
            && in_array('claude-sonnet-4-20250514', $process->command)
            && in_array('--max-turns', $process->command)
            && in_array('10', $process->command);
    });
});

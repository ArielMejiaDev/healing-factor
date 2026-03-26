<?php

use ArielMejiaDev\HealingFactor\Enums\CliTool;

it('has the correct cases', function () {
    expect(CliTool::cases())->toHaveCount(2);
    expect(CliTool::Claude->value)->toBe('claude');
    expect(CliTool::OpenCode->value)->toBe('opencode');
});

it('returns the correct binary name', function () {
    expect(CliTool::Claude->binary())->toBe('claude');
    expect(CliTool::OpenCode->binary())->toBe('opencode');
});

it('builds a basic claude command with skip permissions', function () {
    $cmd = CliTool::Claude->buildCommand('Fix this bug');

    expect($cmd)->toBe([
        'claude', '-p', 'Fix this bug', '--output-format', 'json', '--no-session-persistence',
        '--dangerously-skip-permissions',
    ]);
});

it('builds a claude command with model and max turns', function () {
    $cmd = CliTool::Claude->buildCommand('Fix this bug', 'claude-sonnet-4-20250514', 10);

    expect($cmd)->toBe([
        'claude', '-p', 'Fix this bug', '--output-format', 'json', '--no-session-persistence',
        '--dangerously-skip-permissions',
        '--model', 'claude-sonnet-4-20250514',
        '--max-turns', '10',
    ]);
});

it('builds a basic opencode command', function () {
    $cmd = CliTool::OpenCode->buildCommand('Fix this bug');

    expect($cmd)->toBe([
        'opencode', 'run', 'Fix this bug', '--format', 'json',
    ]);
});

it('builds an opencode command with model', function () {
    $cmd = CliTool::OpenCode->buildCommand('Fix this bug', 'gpt-4');

    expect($cmd)->toBe([
        'opencode', 'run', 'Fix this bug', '--format', 'json',
        '-m', 'gpt-4',
    ]);
});

it('does not include model flag when model is null', function () {
    $cmd = CliTool::Claude->buildCommand('Fix it', null, null);

    expect($cmd)->not->toContain('--model');
});

it('does not include max-turns flag when maxTurns is null', function () {
    $cmd = CliTool::Claude->buildCommand('Fix it', null, null);

    expect($cmd)->not->toContain('--max-turns');
});

it('does not include skip permissions for opencode', function () {
    $cmd = CliTool::OpenCode->buildCommand('Fix it');

    expect($cmd)->not->toContain('--dangerously-skip-permissions');
});

<?php

namespace ArielMejiaDev\HealingFactor\Enums;

enum CliTool: string
{
    case Claude = 'claude';
    case OpenCode = 'opencode';

    public function binary(): string
    {
        return match ($this) {
            self::Claude => 'claude',
            self::OpenCode => 'opencode',
        };
    }

    /** Build the full CLI command array (safe from injection — array form). */
    public function buildCommand(string $prompt, ?string $model = null, ?int $maxTurns = null): array
    {
        return match ($this) {
            self::Claude => $this->buildClaudeCommand($prompt, $model, $maxTurns),
            self::OpenCode => $this->buildOpenCodeCommand($prompt, $model),
        };
    }

    private function buildClaudeCommand(string $prompt, ?string $model, ?int $maxTurns): array
    {
        $cmd = ['claude', '-p', $prompt, '--output-format', 'json', '--no-session-persistence', '--dangerously-skip-permissions'];
        if ($model) {
            $cmd = array_merge($cmd, ['--model', $model]);
        }
        if ($maxTurns) {
            $cmd = array_merge($cmd, ['--max-turns', (string) $maxTurns]);
        }

        return $cmd;
    }

    private function buildOpenCodeCommand(string $prompt, ?string $model): array
    {
        $cmd = ['opencode', 'run', $prompt, '--format', 'json'];
        if ($model) {
            $cmd = array_merge($cmd, ['-m', $model]);
        }

        return $cmd;
    }
}

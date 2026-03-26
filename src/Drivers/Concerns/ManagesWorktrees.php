<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Concerns;

use ArielMejiaDev\HealingFactor\Support\HealingFactorLogger;
use Illuminate\Support\Facades\Process;

trait ManagesWorktrees
{
    protected function createWorktree(string $basePath, string $branchName): ?string
    {
        $worktreePath = sys_get_temp_dir().'/healing-factor-'.md5($branchName.microtime());

        $result = Process::path($basePath)
            ->timeout(30)
            ->run(['git', 'worktree', 'add', $worktreePath, '-b', $branchName]);

        if ($result->successful()) {
            HealingFactorLogger::info("Worktree created on branch: {$branchName}", [
                'path' => $worktreePath,
            ]);

            return $worktreePath;
        }

        HealingFactorLogger::error('Failed to create git worktree.', [
            'branch' => $branchName,
            'error' => $result->errorOutput(),
        ]);

        return null;
    }

    protected function removeWorktree(string $basePath, string $worktreePath): void
    {
        HealingFactorLogger::info('Cleaning up worktree...', ['path' => $worktreePath]);

        $result = Process::path($basePath)
            ->timeout(30)
            ->run(['git', 'worktree', 'remove', $worktreePath, '--force']);

        if (! $result->successful()) {
            HealingFactorLogger::warning('Failed to remove git worktree. Manual cleanup may be required.', [
                'worktree_path' => $worktreePath,
                'error' => $result->errorOutput(),
            ]);
        }
    }
}

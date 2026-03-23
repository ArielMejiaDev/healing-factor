<?php

namespace ArielMejiaDev\XFactor\Commands;

use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Console\Command;

class XFactorPruneCommand extends Command
{
    protected $signature = 'x-factor:prune {--days= : Number of days to retain resolved/failed issues}';

    protected $description = 'Prune old resolved and failed X-Factor issues, and recover stale resolving issues.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('x-factor.retention_days', 30));

        $count = Issue::stale($days)->delete();

        $this->info("Pruned {$count} issues older than {$days} days.");

        $staleCount = Issue::staleResolving()->update([
            'status' => IssueStatus::Failed,
            'failure_reason' => 'Marked as failed by prune command: stuck in resolving status beyond timeout.',
            'failed_at' => now(),
        ]);

        if ($staleCount > 0) {
            $this->warn("Recovered {$staleCount} issues stuck in 'resolving' status.");
        }

        return self::SUCCESS;
    }
}

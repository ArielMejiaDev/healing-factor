<?php

namespace ArielMejiaDev\XFactor\Commands;

use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Console\Command;

class XFactorRecoverStaleCommand extends Command
{
    protected $signature = 'x-factor:recover-stale {--minutes= : Override the timeout threshold in minutes}';

    protected $description = 'Mark issues stuck in resolving status as failed so they can be retried.';

    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: 0);

        $count = Issue::staleResolving($minutes)->update([
            'status' => IssueStatus::Failed,
            'failure_reason' => 'Resolution timed out: stuck in resolving status beyond the configured timeout.',
            'failed_at' => now(),
        ]);

        if ($count > 0) {
            $this->warn("Recovered {$count} stale issue(s) — marked as failed.");
        } else {
            $this->info('No stale resolving issues found.');
        }

        return self::SUCCESS;
    }
}

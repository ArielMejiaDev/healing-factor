<?php

namespace ArielMejiaDev\HealingFactor\Commands;

use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Console\Command;

class HealingFactorRetryCommand extends Command
{
    protected $signature = 'healing-factor:retry {id : The ID of the failed issue to retry}';

    protected $description = 'Retry a failed Healing-Factor issue resolution.';

    public function handle(): int
    {
        $issue = Issue::find($this->argument('id'));

        if (! $issue) {
            $this->error('Issue not found.');

            return self::FAILURE;
        }

        if ($issue->status !== IssueStatus::Failed) {
            $this->error("Issue is not in 'failed' status. Current status: {$issue->status->value}");

            return self::FAILURE;
        }

        $issue->update([
            'status' => IssueStatus::Pending,
            'failure_reason' => null,
            'failed_at' => null,
        ]);

        ResolveIssue::dispatch($issue);

        $this->info("Issue #{$issue->id} has been reset to pending and dispatched for resolution.");

        return self::SUCCESS;
    }
}

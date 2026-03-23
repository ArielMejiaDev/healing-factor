<?php

namespace ArielMejiaDev\XFactor\Commands;

use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Jobs\ResolveIssue;
use ArielMejiaDev\XFactor\Models\Issue;
use Illuminate\Console\Command;

class XFactorRetryCommand extends Command
{
    protected $signature = 'x-factor:retry {id : The ID of the failed issue to retry}';

    protected $description = 'Retry a failed X-Factor issue resolution.';

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

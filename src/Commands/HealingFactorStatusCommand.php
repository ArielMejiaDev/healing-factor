<?php

namespace ArielMejiaDev\HealingFactor\Commands;

use ArielMejiaDev\HealingFactor\Models\Issue;
use Illuminate\Console\Command;

class HealingFactorStatusCommand extends Command
{
    protected $signature = 'healing-factor:status {--limit=20 : Number of issues to display}';

    protected $description = 'Display the status of Healing-Factor issues.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $issues = Issue::query()
            ->latest()
            ->limit($limit)
            ->get();

        if ($issues->isEmpty()) {
            $this->info('No issues found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'Status', 'Category', 'CLI Tool', 'Attempts', 'Created'],
            $issues->map(fn (Issue $issue) => [
                $issue->id,
                mb_substr($issue->title, 0, 50),
                $issue->status->value,
                $issue->category ?? '-',
                $issue->cli_tool ?? '-',
                $issue->attempts,
                $issue->created_at->diffForHumans(),
            ])
        );

        $this->newLine();
        $this->info('Summary:');
        $this->line('  Total: '.Issue::count());
        $this->line('  Pending: '.Issue::pending()->count());
        $this->line('  Resolving: '.Issue::resolving()->count());
        $this->line('  Resolved: '.Issue::resolved()->count());
        $this->line('  Failed: '.Issue::failed()->count());

        return self::SUCCESS;
    }
}

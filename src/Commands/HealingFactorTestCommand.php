<?php

namespace ArielMejiaDev\HealingFactor\Commands;

use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\Services\FingerprintGenerator;
use Illuminate\Console\Command;

class HealingFactorTestCommand extends Command
{
    protected $signature = 'healing-factor:test
        {--exception=ErrorException : The exception class to simulate}
        {--sync : Run the resolution synchronously instead of dispatching to the queue}';

    protected $description = 'Create a test issue and dispatch it for resolution.';

    public function handle(FingerprintGenerator $fingerprinter): int
    {
        $exceptionClass = $this->option('exception');

        $issue = Issue::create([
            'fingerprint' => $fingerprinter->generate($exceptionClass, 'Test exception from healing-factor:test'),
            'source' => 'test',
            'title' => "{$exceptionClass}: Test exception from healing-factor:test",
            'exception_class' => $exceptionClass,
            'exception_message' => 'Test exception from healing-factor:test',
            'status' => 'pending',
        ]);

        $this->info("Test issue #{$issue->id} created.");

        if ($this->option('sync')) {
            $this->info('Running resolution synchronously...');
            ResolveIssue::dispatchSync($issue);
        } else {
            ResolveIssue::dispatch($issue);
            $this->info('Issue dispatched to queue for resolution.');
        }

        $issue->refresh();
        $this->info("Issue status: {$issue->status->value}");

        return self::SUCCESS;
    }
}

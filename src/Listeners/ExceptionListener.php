<?php

namespace ArielMejiaDev\HealingFactor\Listeners;

use ArielMejiaDev\HealingFactor\Events\IssueCreated;
use ArielMejiaDev\HealingFactor\Facades\HealingFactor;
use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\Services\Debouncer;
use ArielMejiaDev\HealingFactor\Services\FingerprintGenerator;
use ArielMejiaDev\HealingFactor\Support\HealingFactorLogger;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;

class ExceptionListener
{
    protected static bool $processing = false;

    public function __construct(
        protected Debouncer $debouncer,
        protected FingerprintGenerator $fingerprinter,
    ) {}

    public function handleMessageLogged(MessageLogged $event): void
    {
        if ($event->level !== 'error' && $event->level !== 'critical') {
            return;
        }

        $exception = $event->context['exception'] ?? null;
        if (! $exception instanceof \Throwable) {
            return;
        }

        $this->processException($exception);
    }

    public function handleJobException(JobExceptionOccurred $event): void
    {
        $this->processException($event->exception);
    }

    protected function processException(\Throwable $exception): void
    {
        // Guard against recursion (e.g. Healing-Factor's own DB errors triggering the listener)
        if (static::$processing) {
            return;
        }

        static::$processing = true;

        try {
            $this->doProcessException($exception);
        } finally {
            static::$processing = false;
        }
    }

    protected function doProcessException(\Throwable $exception): void
    {
        if (! HealingFactor::isEnabled()) {
            return;
        }

        $exceptionClass = get_class($exception);

        // Check ignored exceptions
        $ignored = config('healing-factor.ignored_exceptions', []);
        foreach ($ignored as $ignoredClass) {
            if ($exceptionClass === $ignoredClass || $exception instanceof $ignoredClass) {
                return;
            }
        }

        // Generate fingerprint (using class + message only, consistent with webhook path)
        $fingerprint = $this->fingerprinter->generate(
            $exceptionClass,
            $exception->getMessage(),
        );

        // Debounce
        if (! $this->debouncer->shouldProcess($fingerprint)) {
            return;
        }

        // Deduplicate
        if (Issue::where('fingerprint', $fingerprint)->whereIn('status', ['pending', 'resolving'])->exists()) {
            return;
        }

        // Create issue
        $issue = Issue::create([
            'fingerprint' => $fingerprint,
            'source' => 'exception_listener',
            'title' => mb_substr($exceptionClass.': '.$exception->getMessage(), 0, 255),
            'exception_class' => $exceptionClass,
            'exception_message' => mb_substr($exception->getMessage(), 0, 5000),
            'stacktrace' => mb_substr($exception->getTraceAsString(), 0, 5000),
            'status' => 'pending',
        ]);

        event(new IssueCreated($issue));
        ResolveIssue::dispatch($issue);

        HealingFactorLogger::info("Issue #{$issue->id} created (status: pending). Job dispatched.", [
            'exception' => $exceptionClass,
        ]);
    }
}

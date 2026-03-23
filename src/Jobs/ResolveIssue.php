<?php

namespace ArielMejiaDev\XFactor\Jobs;

use ArielMejiaDev\XFactor\Events\IssueResolutionFailed;
use ArielMejiaDev\XFactor\Models\Issue;
use ArielMejiaDev\XFactor\Services\IssueResolver;
use ArielMejiaDev\XFactor\Support\XFactorLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveIssue implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3700;   // slightly more than Process timeout

    public int $tries = 1;        // do not retry automatically

    public int $uniqueFor = 3600; // lock for 1 hour

    public function __construct(public Issue $issue, public array $overrides = [])
    {
        $queue = config('x-factor.queue.name');
        $connection = config('x-factor.queue.connection');

        if ($queue) {
            $this->onQueue($queue);
        }
        if ($connection) {
            $this->onConnection($connection);
        }
    }

    public function uniqueId(): string
    {
        return 'x-factor-resolve-'.$this->issue->id;
    }

    public function handle(IssueResolver $resolver): void
    {
        $resolver->resolve($this->issue, $this->overrides);
    }

    public function failed(\Throwable $exception): void
    {
        $reason = mb_substr($exception->getMessage(), 0, 65535);

        $this->issue->refresh();

        if ($this->issue->isResolving()) {
            $this->issue->markFailed($reason);
            XFactorLogger::error('Status: resolving -> failed (job exception)', [
                'issue_id' => $this->issue->id,
                'exception' => get_class($exception),
                'message' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        } else {
            XFactorLogger::error('ResolveIssue job failed.', [
                'issue_id' => $this->issue->id,
                'exception' => get_class($exception),
                'message' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }

        event(new IssueResolutionFailed($this->issue, $reason));
    }
}

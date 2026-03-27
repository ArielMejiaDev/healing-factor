<?php

namespace ArielMejiaDev\HealingFactor\Services;

use ArielMejiaDev\HealingFactor\Contracts\DriverResult;
use ArielMejiaDev\HealingFactor\Enums\IssueStatus;
use ArielMejiaDev\HealingFactor\Events\IssueResolutionFailed;
use ArielMejiaDev\HealingFactor\Events\IssueResolved;
use ArielMejiaDev\HealingFactor\Events\IssueResolving;
use ArielMejiaDev\HealingFactor\HealingFactorManager;
use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\Prompts\PromptBuilder;
use ArielMejiaDev\HealingFactor\Support\HealingFactorLogger;

class IssueResolver
{
    public function __construct(
        protected HealingFactorManager $manager,
        protected PromptBuilder $promptBuilder,
    ) {}

    public function resolve(Issue $issue, array $overrides = []): bool
    {
        // 0. Apply overrides (safe: queue jobs run in isolated processes)
        if (isset($overrides['model'])) {
            config()->set('healing-factor.api.model', $overrides['model']);
            config()->set('healing-factor.model', $overrides['model']);
        }
        if (isset($overrides['max_turns'])) {
            config()->set('healing-factor.api.max_turns', (int) $overrides['max_turns']);
            config()->set('healing-factor.process.max_turns', (int) $overrides['max_turns']);
        }

        // 1. Guard: check if Healing-Factor is explicitly disabled (master switch only).
        //    Environment checks are NOT repeated here because the issue was already
        //    validated at creation time (listener/webhook), and the queue worker may
        //    run in a different APP_ENV than the web process.
        if (! config('healing-factor.enabled', true)) {
            HealingFactorLogger::info('Issue resolution skipped: Healing-Factor is disabled.', [
                'issue_id' => $issue->id,
            ]);

            return false;
        }

        // 2. Atomic status transition: pending -> resolving
        if (! $issue->markResolving()) {
            HealingFactorLogger::info('Issue resolution skipped: already being processed or resolved.', [
                'issue_id' => $issue->id,
            ]);

            return false;
        }

        HealingFactorLogger::info('Status: pending -> resolving', [
            'issue_id' => $issue->id,
            'title' => $issue->title,
        ]);

        event(new IssueResolving($issue));
        $issue->incrementAttempts();

        // 3. Determine category
        $category = $this->detectCategory($issue);
        $issue->update(['category' => $category]);

        HealingFactorLogger::info('Category detected: '.($category ?? 'none (using failover defaults)'), [
            'issue_id' => $issue->id,
        ]);

        // 4. Check dry-run mode
        if (config('healing-factor.dry_run')) {
            HealingFactorLogger::info('[DRY RUN] Would resolve issue. Resetting to pending.', ['issue_id' => $issue->id]);
            Issue::query()
                ->whereKey($issue->id)
                ->update(['status' => IssueStatus::Pending]);
            $issue->refresh();

            return true;
        }

        // 5. Build prompt
        $prompt = $this->promptBuilder->build($issue);

        HealingFactorLogger::info("Prompt built, branch created: {$issue->branch_name}", [
            'issue_id' => $issue->id,
        ]);

        // 6. Get driver (possibly category-specific)
        $driverName = config('healing-factor.driver', 'cli');
        $driver = $this->manager->driverForCategory($category);
        $issue->update(['cli_tool' => $driverName === 'api' ? 'api' : config('healing-factor.cli_tool', 'claude')]);

        HealingFactorLogger::info("Executing {$driverName} driver", [
            'issue_id' => $issue->id,
            'timeout' => config('healing-factor.process.timeout', 3600),
        ]);

        // 7. Execute
        $result = $driver->resolve($issue, $prompt);

        // 8. Handle result
        if ($result->success) {
            return $this->handleSuccess($issue, $result);
        }

        return $this->handleFailure($issue, $result);
    }

    public function detectCategory(Issue $issue): ?string
    {
        $exceptionClass = $issue->exception_class;
        if (! $exceptionClass) {
            return null;
        }

        $categories = config('healing-factor.categories', []);
        foreach ($categories as $name => $config) {
            $exceptions = $config['exceptions'] ?? [];
            foreach ($exceptions as $exception) {
                if ($exceptionClass === $exception || is_subclass_of($exceptionClass, $exception)) {
                    return $name;
                }
            }
        }

        return null; // failover
    }

    protected function handleSuccess(Issue $issue, DriverResult $result): bool
    {
        $issue->update([
            'cli_output' => mb_substr($result->output, 0, 65535),
            'cli_error_output' => mb_substr($result->errorOutput, 0, 65535),
        ]);

        $prUrl = $this->extractPrUrl($result->output) ?? $this->extractPrUrl($result->errorOutput);
        $issue->markResolved($prUrl);

        HealingFactorLogger::info('Status: resolving -> resolved', ['issue_id' => $issue->id]);
        event(new IssueResolved($issue));

        return true;
    }

    protected function handleFailure(Issue $issue, DriverResult $result): bool
    {
        $reason = mb_substr($result->errorOutput ?: $result->output, 0, 65535);
        $issue->update([
            'cli_output' => mb_substr($result->output, 0, 65535),
            'cli_error_output' => mb_substr($result->errorOutput, 0, 65535),
        ]);
        $issue->markFailed($reason);

        HealingFactorLogger::error('Status: resolving -> failed', [
            'issue_id' => $issue->id,
            'error_output' => mb_substr($result->errorOutput, 0, 500),
            'exit_code' => $result->exitCode,
        ]);
        event(new IssueResolutionFailed($issue, $reason));

        return false;
    }

    protected function extractPrUrl(string $output): ?string
    {
        if (preg_match('#https://github\.com/[^\s]+/pull/\d+#', $output, $matches)) {
            return $matches[0];
        }

        return null;
    }
}

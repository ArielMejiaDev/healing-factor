<?php

namespace ArielMejiaDev\XFactor\Services;

use ArielMejiaDev\XFactor\Contracts\DriverResult;
use ArielMejiaDev\XFactor\Enums\IssueStatus;
use ArielMejiaDev\XFactor\Events\IssueResolutionFailed;
use ArielMejiaDev\XFactor\Events\IssueResolved;
use ArielMejiaDev\XFactor\Events\IssueResolving;
use ArielMejiaDev\XFactor\Facades\XFactor;
use ArielMejiaDev\XFactor\Models\Issue;
use ArielMejiaDev\XFactor\Prompts\PromptBuilder;
use ArielMejiaDev\XFactor\Support\XFactorLogger;
use ArielMejiaDev\XFactor\XFactorManager;

class IssueResolver
{
    public function __construct(
        protected XFactorManager $manager,
        protected PromptBuilder $promptBuilder,
    ) {}

    public function resolve(Issue $issue, array $overrides = []): bool
    {
        // 0. Apply overrides (safe: queue jobs run in isolated processes)
        if (isset($overrides['model'])) {
            config()->set('x-factor.api.model', $overrides['model']);
            config()->set('x-factor.model', $overrides['model']);
        }
        if (isset($overrides['max_turns'])) {
            config()->set('x-factor.api.max_turns', (int) $overrides['max_turns']);
            config()->set('x-factor.process.max_turns', (int) $overrides['max_turns']);
        }

        // 1. Guard: check if X-Factor is enabled and in an allowed environment
        if (! XFactor::isEnabled()) {
            XFactorLogger::info('Issue resolution skipped: X-Factor is disabled or not in an allowed environment.', [
                'issue_id' => $issue->id,
            ]);

            return false;
        }

        // 1. Atomic status transition: pending -> resolving
        if (! $issue->markResolving()) {
            XFactorLogger::info('Issue resolution skipped: already being processed or resolved.', [
                'issue_id' => $issue->id,
            ]);

            return false;
        }

        XFactorLogger::info("Status: pending -> resolving", [
            'issue_id' => $issue->id,
            'title' => $issue->title,
        ]);

        event(new IssueResolving($issue));
        $issue->incrementAttempts();

        // 2. Determine category
        $category = $this->detectCategory($issue);
        $issue->update(['category' => $category]);

        XFactorLogger::info("Category detected: " . ($category ?? 'none (using failover defaults)'), [
            'issue_id' => $issue->id,
        ]);

        // 3. Check dry-run mode
        if (config('x-factor.dry_run')) {
            XFactorLogger::info('[DRY RUN] Would resolve issue. Resetting to pending.', ['issue_id' => $issue->id]);
            Issue::query()
                ->whereKey($issue->id)
                ->update(['status' => IssueStatus::Pending]);
            $issue->refresh();

            return true;
        }

        // 4. Build prompt
        $prompt = $this->promptBuilder->build($issue);

        XFactorLogger::info("Prompt built, branch created: {$issue->branch_name}", [
            'issue_id' => $issue->id,
        ]);

        // 5. Get driver (possibly category-specific)
        $driverName = config('x-factor.driver', 'cli');
        $driver = $this->manager->driverForCategory($category);
        $issue->update(['cli_tool' => $driverName === 'api' ? 'api' : config('x-factor.cli_tool', 'claude')]);

        XFactorLogger::info("Executing {$driverName} driver", [
            'issue_id' => $issue->id,
            'timeout' => config('x-factor.process.timeout', 3600),
        ]);

        // 6. Execute
        $result = $driver->resolve($issue, $prompt);

        // 7. Handle result
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

        $categories = config('x-factor.categories', []);
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

        XFactorLogger::info("Status: resolving -> resolved", ['issue_id' => $issue->id]);
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

        XFactorLogger::error("Status: resolving -> failed", [
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

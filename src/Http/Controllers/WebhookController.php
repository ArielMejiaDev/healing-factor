<?php

namespace ArielMejiaDev\HealingFactor\Http\Controllers;

use ArielMejiaDev\HealingFactor\Contracts\MonitorContract;
use ArielMejiaDev\HealingFactor\Events\IssueCreated;
use ArielMejiaDev\HealingFactor\Facades\HealingFactor;
use ArielMejiaDev\HealingFactor\Jobs\ResolveIssue;
use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\Services\Debouncer;
use ArielMejiaDev\HealingFactor\Services\FingerprintGenerator;
use ArielMejiaDev\HealingFactor\Support\HealingFactorLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MonitorContract $monitor,
        Debouncer $debouncer,
        FingerprintGenerator $fingerprinter,
    ): JsonResponse {
        if (! HealingFactor::isEnabled()) {
            return response()->json(['message' => 'Healing-Factor is disabled.'], 200);
        }

        if (! $monitor->shouldProcess($request)) {
            return response()->json(['message' => 'Event ignored.'], 200);
        }

        $data = $monitor->parseWebhook($request);
        if (! $data) {
            return response()->json(['message' => 'Unable to parse webhook.'], 422);
        }

        // Generate fingerprint
        $fingerprint = isset($data['exception_class'])
            ? $fingerprinter->generate($data['exception_class'], $data['exception_message'] ?? null)
            : $fingerprinter->generateFromTitle($data['title'], $data['source'] ?? 'webhook');

        // Debounce check
        if (! $debouncer->shouldProcess($fingerprint)) {
            return response()->json(['message' => 'Debounced.'], 200);
        }

        // Check for ignored exceptions (including subclasses)
        if (isset($data['exception_class']) && $this->isIgnoredException($data['exception_class'])) {
            return response()->json(['message' => 'Exception ignored.'], 200);
        }

        // Deduplicate: check if issue with same fingerprint is pending/resolving
        $existing = Issue::query()
            ->where('fingerprint', $fingerprint)
            ->whereIn('status', ['pending', 'resolving'])
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'Issue already being processed.'], 200);
        }

        // Create issue
        $issue = Issue::create(array_merge($data, [
            'fingerprint' => $fingerprint,
            'status' => 'pending',
        ]));

        event(new IssueCreated($issue));
        ResolveIssue::dispatch($issue);

        HealingFactorLogger::info("Issue #{$issue->id} created (status: pending). Job dispatched.", [
            'title' => $data['title'] ?? 'unknown',
        ]);

        return response()->json(['message' => 'Issue created.', 'issue_id' => $issue->id], 201);
    }

    protected function isIgnoredException(string $exceptionClass): bool
    {
        $ignored = config('healing-factor.ignored_exceptions', []);

        foreach ($ignored as $ignoredClass) {
            if ($exceptionClass === $ignoredClass || is_subclass_of($exceptionClass, $ignoredClass)) {
                return true;
            }
        }

        return false;
    }
}

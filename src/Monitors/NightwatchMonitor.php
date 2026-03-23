<?php

namespace ArielMejiaDev\XFactor\Monitors;

use ArielMejiaDev\XFactor\Contracts\MonitorContract;
use Illuminate\Http\Request;

class NightwatchMonitor implements MonitorContract
{
    public function shouldProcess(Request $request): bool
    {
        $event = $request->input('event');

        return in_array($event, ['issue.opened', 'issue.reopened'], true);
    }

    public function parseWebhook(Request $request): ?array
    {
        $payload = $request->input('payload');

        if (empty($payload)) {
            return null;
        }

        $issue = $payload['issue'] ?? [];
        $details = $issue['details'] ?? [];

        // Real Nightwatch payload uses details.class / details.message.
        // Fall back to legacy fields for backward compatibility.
        $exceptionClass = $details['class'] ?? $issue['exception_class'] ?? null;
        $exceptionMessage = $details['message'] ?? $issue['message'] ?? null;

        // Nightwatch doesn't send a full stacktrace, but provides file + line.
        $stacktrace = $issue['stacktrace'] ?? null;
        if (! $stacktrace && isset($details['file'])) {
            $line = $details['line'] ?? '?';
            $stacktrace = "#0 {$details['file']}({$line})";
        }

        return [
            'source' => 'nightwatch',
            'organization_id' => $payload['organization_id'] ?? null,
            'application_id' => $payload['application_id'] ?? null,
            'environment_id' => $payload['environment']['id'] ?? null,
            'title' => $issue['title'] ?? 'Unknown Issue',
            'exception_class' => $exceptionClass,
            'exception_message' => $exceptionMessage,
            'stacktrace' => $stacktrace,
            'payload' => $request->all(),
        ];
    }

    public function verifySignature(Request $request, string $secret): bool
    {
        $signature = $request->header('Nightwatch-Signature')
            ?? $request->header('X-Nightwatch-Signature')
            ?? $request->header('X-Webhook-Signature');

        if (! $signature) {
            return false;
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computed, $signature);
    }
}

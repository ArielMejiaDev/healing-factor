<?php

namespace ArielMejiaDev\XFactor\Monitors;

use ArielMejiaDev\XFactor\Contracts\MonitorContract;
use Illuminate\Http\Request;

class BugsnagMonitor implements MonitorContract
{
    public function shouldProcess(Request $request): bool
    {
        $trigger = $request->input('trigger');

        return in_array($trigger, ['exception', 'firstException'], true);
    }

    public function parseWebhook(Request $request): ?array
    {
        $error = $request->input('error');

        if (empty($error)) {
            return null;
        }

        return [
            'source' => 'bugsnag',
            'title' => ($error['exceptionClass'] ?? 'Unknown').': '.($error['message'] ?? ''),
            'exception_class' => $error['exceptionClass'] ?? null,
            'exception_message' => $error['message'] ?? null,
            'stacktrace' => isset($error['stackTrace']) ? $this->abbreviateStacktrace($error['stackTrace']) : null,
            'payload' => $request->all(),
        ];
    }

    public function verifySignature(Request $request, string $secret): bool
    {
        $token = $request->header('Authorization');

        if (! $token) {
            return false;
        }

        // Bugsnag uses a bearer token style
        $token = str_replace('Bearer ', '', $token);

        return hash_equals($secret, $token);
    }

    protected function abbreviateStacktrace(mixed $stackTrace): string
    {
        if (is_string($stackTrace)) {
            return mb_substr($stackTrace, 0, 5000);
        }

        if (is_array($stackTrace)) {
            $lines = array_map(function ($frame) {
                $file = $frame['file'] ?? 'unknown';
                $line = $frame['lineNumber'] ?? '?';
                $method = $frame['method'] ?? 'unknown';

                return "{$file}:{$line} in {$method}";
            }, array_slice($stackTrace, 0, 20));

            return implode("\n", $lines);
        }

        return '';
    }
}

<?php

namespace ArielMejiaDev\HealingFactor\Support;

use Illuminate\Support\Facades\Log;

class HealingFactorLogger
{
    public static function info(string $message, array $context = []): void
    {
        static::log('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        static::log('warning', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        static::log('debug', $message, $context);
    }

    protected static function log(string $level, string $message, array $context = []): void
    {
        Log::channel(config('healing-factor.log_channel', 'healing-factor'))->{$level}($message, $context);

        if (app()->runningInConsole()) {
            static::writeToStderr($level, $message, $context);
        }
    }

    protected static function writeToStderr(string $level, string $message, array $context = []): void
    {
        $tag = match ($level) {
            'error' => "\033[31m[HEALING-FACTOR ERROR]\033[0m",
            'warning' => "\033[33m[HEALING-FACTOR WARN]\033[0m",
            'info' => "\033[36m[HEALING-FACTOR]\033[0m",
            'debug' => "\033[90m[HEALING-FACTOR DEBUG]\033[0m",
            default => '[HEALING-FACTOR]',
        };

        $line = "{$tag} {$message}";

        if (! empty($context)) {
            $summary = static::summarizeContext($context);
            if ($summary !== '') {
                $line .= " {$summary}";
            }
        }

        file_put_contents('php://stderr', $line.PHP_EOL);
    }

    protected static function summarizeContext(array $context): string
    {
        $parts = [];

        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $display = is_string($value) && mb_strlen($value) > 120
                    ? mb_substr($value, 0, 120).'…'
                    : $value;
                $parts[] = "{$key}={$display}";
            }
        }

        return implode(' ', $parts);
    }
}

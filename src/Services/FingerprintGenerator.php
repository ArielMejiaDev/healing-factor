<?php

namespace ArielMejiaDev\HealingFactor\Services;

class FingerprintGenerator
{
    public function generate(
        ?string $exceptionClass = null,
        ?string $message = null,
        ?string $file = null,
        ?int $line = null,
    ): string {
        $data = implode('|', array_filter([
            $exceptionClass,
            $message,
            $file,
            $line !== null ? (string) $line : null,
        ]));

        return hash('sha256', $data);
    }

    public function generateFromTitle(string $title, string $source = 'nightwatch'): string
    {
        return hash('sha256', "{$source}|{$title}");
    }
}

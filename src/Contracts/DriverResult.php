<?php

namespace ArielMejiaDev\HealingFactor\Contracts;

final readonly class DriverResult
{
    public function __construct(
        public bool $success,
        public string $output,
        public string $errorOutput,
        public int $exitCode,
    ) {}
}

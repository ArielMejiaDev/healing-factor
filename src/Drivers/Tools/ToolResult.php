<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

final readonly class ToolResult
{
    public function __construct(
        public string $output,
        public bool $isError,
    ) {}
}

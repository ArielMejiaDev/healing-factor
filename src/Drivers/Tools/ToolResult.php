<?php

namespace ArielMejiaDev\XFactor\Drivers\Tools;

final readonly class ToolResult
{
    public function __construct(
        public string $output,
        public bool $isError,
    ) {}
}

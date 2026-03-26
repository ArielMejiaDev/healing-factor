<?php

namespace ArielMejiaDev\HealingFactor\Drivers\Tools;

interface ToolContract
{
    public function name(): string;

    /** @return array<string, mixed> JSON schema for the Anthropic API tool definition. */
    public function definition(): array;

    /** @param array<string, mixed> $input */
    public function execute(array $input): string;
}

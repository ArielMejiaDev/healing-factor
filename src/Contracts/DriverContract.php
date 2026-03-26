<?php

namespace ArielMejiaDev\HealingFactor\Contracts;

use ArielMejiaDev\HealingFactor\Models\Issue;

interface DriverContract
{
    public function resolve(Issue $issue, string $prompt): DriverResult;
}

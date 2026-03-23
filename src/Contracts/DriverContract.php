<?php

namespace ArielMejiaDev\XFactor\Contracts;

use ArielMejiaDev\XFactor\Models\Issue;

interface DriverContract
{
    public function resolve(Issue $issue, string $prompt): DriverResult;
}

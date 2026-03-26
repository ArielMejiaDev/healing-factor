<?php

namespace ArielMejiaDev\HealingFactor\Events;

use ArielMejiaDev\HealingFactor\Models\Issue;

class IssueResolutionFailed
{
    public function __construct(public Issue $issue, public string $reason) {}
}

<?php

namespace ArielMejiaDev\HealingFactor\Events;

use ArielMejiaDev\HealingFactor\Models\Issue;

class IssueResolved
{
    public function __construct(public Issue $issue) {}
}

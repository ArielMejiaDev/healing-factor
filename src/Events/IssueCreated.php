<?php

namespace ArielMejiaDev\HealingFactor\Events;

use ArielMejiaDev\HealingFactor\Models\Issue;

class IssueCreated
{
    public function __construct(public Issue $issue) {}
}

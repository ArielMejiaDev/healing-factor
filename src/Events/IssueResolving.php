<?php

namespace ArielMejiaDev\HealingFactor\Events;

use ArielMejiaDev\HealingFactor\Models\Issue;

class IssueResolving
{
    public function __construct(public Issue $issue) {}
}

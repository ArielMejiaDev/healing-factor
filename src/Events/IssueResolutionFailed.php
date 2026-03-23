<?php

namespace ArielMejiaDev\XFactor\Events;

use ArielMejiaDev\XFactor\Models\Issue;

class IssueResolutionFailed
{
    public function __construct(public Issue $issue, public string $reason) {}
}

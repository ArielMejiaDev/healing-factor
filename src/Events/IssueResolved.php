<?php

namespace ArielMejiaDev\XFactor\Events;

use ArielMejiaDev\XFactor\Models\Issue;

class IssueResolved
{
    public function __construct(public Issue $issue) {}
}

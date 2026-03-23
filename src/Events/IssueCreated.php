<?php

namespace ArielMejiaDev\XFactor\Events;

use ArielMejiaDev\XFactor\Models\Issue;

class IssueCreated
{
    public function __construct(public Issue $issue) {}
}

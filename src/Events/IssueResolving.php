<?php

namespace ArielMejiaDev\XFactor\Events;

use ArielMejiaDev\XFactor\Models\Issue;

class IssueResolving
{
    public function __construct(public Issue $issue) {}
}

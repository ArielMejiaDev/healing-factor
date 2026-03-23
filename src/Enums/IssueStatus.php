<?php

namespace ArielMejiaDev\XFactor\Enums;

enum IssueStatus: string
{
    case Pending = 'pending';
    case Resolving = 'resolving';
    case Resolved = 'resolved';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Resolved, self::Failed]);
    }
}

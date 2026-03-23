<?php

namespace ArielMejiaDev\XFactor\Contracts;

use ArielMejiaDev\XFactor\Models\Issue;

interface PromptBuilderContract
{
    public function build(Issue $issue): string;
}

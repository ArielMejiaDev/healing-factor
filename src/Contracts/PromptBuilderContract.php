<?php

namespace ArielMejiaDev\HealingFactor\Contracts;

use ArielMejiaDev\HealingFactor\Models\Issue;

interface PromptBuilderContract
{
    public function build(Issue $issue): string;
}

<?php

namespace ArielMejiaDev\XFactor\Services;

use Illuminate\Support\Str;

class BranchNameGenerator
{
    public function generate(string $title): string
    {
        $prefix = config('x-factor.pr.branch_prefix', 'x-factor/fix');
        $slug = Str::slug(Str::limit($title, 40, ''), '-');
        $random = Str::random(6);

        return "{$prefix}-{$slug}-{$random}";
    }
}

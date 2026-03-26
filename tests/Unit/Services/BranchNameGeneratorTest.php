<?php

use ArielMejiaDev\HealingFactor\Services\BranchNameGenerator;

beforeEach(function () {
    $this->generator = new BranchNameGenerator;
});

it('generates a branch name with the configured prefix', function () {
    $branch = $this->generator->generate('Fix login error');

    expect($branch)->toStartWith('healing-factor/fix-');
});

it('includes a slugified title', function () {
    $branch = $this->generator->generate('Fix login error');

    expect($branch)->toContain('fix-login-error');
});

it('truncates long titles', function () {
    $longTitle = str_repeat('a very long title ', 10);
    $branch = $this->generator->generate($longTitle);

    // Total length should be reasonable
    expect(strlen($branch))->toBeLessThan(100);
});

it('appends a random suffix', function () {
    $branch1 = $this->generator->generate('Fix bug');
    $branch2 = $this->generator->generate('Fix bug');

    // They should differ because of the random suffix
    expect($branch1)->not->toBe($branch2);
});

it('uses custom branch prefix from config', function () {
    config()->set('healing-factor.pr.branch_prefix', 'auto/fix');

    $branch = $this->generator->generate('Some error');

    expect($branch)->toStartWith('auto/fix-');
});

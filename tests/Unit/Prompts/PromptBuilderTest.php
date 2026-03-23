<?php

use ArielMejiaDev\XFactor\Models\Issue;
use ArielMejiaDev\XFactor\Prompts\PromptBuilder;
use ArielMejiaDev\XFactor\Services\BranchNameGenerator;

beforeEach(function () {
    $this->builder = new PromptBuilder(new BranchNameGenerator);
});

it('builds a default prompt for nightwatch issues', function () {
    $issue = Issue::factory()->create([
        'source' => 'nightwatch',
        'title' => 'ErrorException: Undefined variable',
        'exception_class' => 'ErrorException',
        'exception_message' => 'Undefined variable $foo',
        'organization_id' => 'org-123',
        'application_id' => 'app-456',
        'environment_id' => 'env-789',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)->toContain('Laravel project')
        ->toContain('ErrorException: Undefined variable')
        ->toContain('Undefined variable $foo')
        ->toContain('Nightwatch MCP')
        ->toContain('org-123')
        ->toContain('gh pr create');
});

it('builds a prompt for bugsnag issues', function () {
    $issue = Issue::factory()->create([
        'source' => 'bugsnag',
        'title' => 'RuntimeException: Oops',
        'exception_class' => 'RuntimeException',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)->toContain('Bugsnag');
});

it('builds a prompt for exception listener issues', function () {
    $issue = Issue::factory()->create([
        'source' => 'exception_listener',
        'title' => 'TypeError: Wrong type',
        'exception_class' => 'TypeError',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)->toContain('exception listener');
});

it('includes stacktrace when present', function () {
    $issue = Issue::factory()->create([
        'stacktrace' => '#0 /app/Http/Controller.php:42',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)->toContain('#0 /app/Http/Controller.php:42');
});

it('uses custom category prompt when configured with safety preamble', function () {
    config()->set('x-factor.categories.quick_fixes.prompt', 'Custom prompt for {title}');

    $issue = Issue::factory()->create([
        'category' => 'quick_fixes',
        'title' => 'Fix login bug',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)
        ->toContain('Custom prompt for Fix login bug')
        ->toContain('already on branch')
        ->toContain('Do NOT switch branches')
        ->toContain('gh pr create')
        ->toContain('git push');
});

it('uses top-level custom prompt as fallback with safety preamble', function () {
    config()->set('x-factor.prompt', 'Global prompt: {exception_class}');

    $issue = Issue::factory()->create([
        'exception_class' => 'ErrorException',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)
        ->toContain('Global prompt: ErrorException')
        ->toContain('already on branch')
        ->toContain('Do NOT switch branches');
});

it('sets branch_name on the issue', function () {
    $issue = Issue::factory()->create();

    $this->builder->build($issue);

    $issue->refresh();
    expect($issue->branch_name)->not->toBeNull()
        ->toStartWith('x-factor/fix-');
});

it('includes draft flag in PR command when configured', function () {
    config()->set('x-factor.pr.draft', true);

    $issue = Issue::factory()->create();
    $prompt = $this->builder->build($issue);

    expect($prompt)->toContain('--draft');
});

it('tells the AI it is already on the branch', function () {
    $issue = Issue::factory()->create([
        'title' => 'ErrorException: Undefined variable',
        'exception_class' => 'ErrorException',
    ]);

    $prompt = $this->builder->build($issue);

    expect($prompt)
        ->toContain('already on branch')
        ->toContain('Do NOT switch branches')
        ->toContain('gh pr create')
        ->toContain('git push')
        ->not->toContain('Make a new branch')
        ->not->toContain('git checkout -b');
});

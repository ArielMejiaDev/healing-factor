<?php

use ArielMejiaDev\HealingFactor\Models\Issue;

it('displays no issues message when table is empty', function () {
    $this->artisan('healing-factor:status')
        ->expectsOutput('No issues found.')
        ->assertExitCode(0);
});

it('displays issues in a table', function () {
    Issue::factory()->pending()->count(2)->create();
    Issue::factory()->resolved()->create();

    $this->artisan('healing-factor:status')
        ->assertExitCode(0);
});

it('respects the limit option', function () {
    Issue::factory()->count(5)->create();

    $this->artisan('healing-factor:status --limit=2')
        ->assertExitCode(0);
});

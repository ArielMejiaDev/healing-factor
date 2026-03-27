<?php

it('runs the install command', function () {
    $this->artisan('healing-factor:install')
        ->expectsConfirmation('Run the migration now?', 'no')
        ->assertExitCode(0);
});

it('checks CLI tool availability when driver is cli', function () {
    config()->set('healing-factor.driver', 'cli');

    $this->artisan('healing-factor:install')
        ->expectsConfirmation('Run the migration now?', 'no')
        ->expectsOutputToContain('Driver: cli')
        ->assertExitCode(0);
});

it('checks ANTHROPIC_API_KEY when driver is api', function () {
    config()->set('healing-factor.driver', 'api');
    config()->set('healing-factor.api_keys.anthropic', null);

    $this->artisan('healing-factor:install')
        ->expectsConfirmation('Run the migration now?', 'no')
        ->expectsOutputToContain('Driver: api')
        ->expectsOutputToContain('ANTHROPIC_API_KEY is not set')
        ->assertExitCode(0);
});

it('does not warn about ANTHROPIC_API_KEY when driver is cli', function () {
    config()->set('healing-factor.driver', 'cli');
    config()->set('healing-factor.api_keys.anthropic', null);

    $this->artisan('healing-factor:install')
        ->expectsConfirmation('Run the migration now?', 'no')
        ->doesntExpectOutputToContain('ANTHROPIC_API_KEY')
        ->assertExitCode(0);
});

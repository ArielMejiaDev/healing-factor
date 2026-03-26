<?php

it('runs the install command', function () {
    $this->artisan('healing-factor:install')
        ->expectsConfirmation('Run the migration now?', 'no')
        ->assertExitCode(0);
});

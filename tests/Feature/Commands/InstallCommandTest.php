<?php

it('runs the install command', function () {
    $this->artisan('x-factor:install')
        ->expectsConfirmation('Run the migration now?', 'no')
        ->assertExitCode(0);
});

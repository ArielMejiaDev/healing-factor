<?php

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('enums are backed enums')
    ->expect('ArielMejiaDev\HealingFactor\Enums')
    ->toBeEnums();

arch('models extend Eloquent Model')
    ->expect('ArielMejiaDev\HealingFactor\Models')
    ->toExtend(Model::class);

arch('jobs implement ShouldQueue')
    ->expect('ArielMejiaDev\HealingFactor\Jobs')
    ->toImplement(ShouldQueue::class);

arch('commands extend Command')
    ->expect('ArielMejiaDev\HealingFactor\Commands')
    ->toExtend(Command::class);

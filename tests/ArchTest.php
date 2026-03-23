<?php

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('enums are backed enums')
    ->expect('ArielMejiaDev\XFactor\Enums')
    ->toBeEnums();

arch('models extend Eloquent Model')
    ->expect('ArielMejiaDev\XFactor\Models')
    ->toExtend(Model::class);

arch('jobs implement ShouldQueue')
    ->expect('ArielMejiaDev\XFactor\Jobs')
    ->toImplement(ShouldQueue::class);

arch('commands extend Command')
    ->expect('ArielMejiaDev\XFactor\Commands')
    ->toExtend(Command::class);

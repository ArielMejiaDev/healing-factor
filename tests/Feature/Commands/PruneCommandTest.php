<?php

use ArielMejiaDev\XFactor\Models\Issue;

it('prunes stale resolved issues', function () {
    Issue::factory()->resolved()->create(['updated_at' => now()->subDays(31)]);
    Issue::factory()->resolved()->create(['updated_at' => now()->subDays(5)]);
    Issue::factory()->pending()->create();

    $this->artisan('x-factor:prune --days=30')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(2);
});

it('prunes stale failed issues', function () {
    Issue::factory()->failed()->create(['updated_at' => now()->subDays(31)]);

    $this->artisan('x-factor:prune --days=30')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(0);
});

it('uses configured retention days as default', function () {
    config()->set('x-factor.retention_days', 7);

    Issue::factory()->resolved()->create(['updated_at' => now()->subDays(8)]);
    Issue::factory()->resolved()->create(['updated_at' => now()->subDays(3)]);

    $this->artisan('x-factor:prune')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(1);
});

it('does not prune pending issues', function () {
    Issue::factory()->pending()->create(['updated_at' => now()->subDays(60)]);

    $this->artisan('x-factor:prune --days=1')
        ->assertExitCode(0);

    expect(Issue::count())->toBe(1);
});

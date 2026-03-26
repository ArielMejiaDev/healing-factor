<?php

use ArielMejiaDev\HealingFactor\Models\Issue;
use ArielMejiaDev\HealingFactor\HealingFactor;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    if (! Schema::hasTable('users')) {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    config()->set('healing-factor.dashboard.enabled', true);
    config()->set('healing-factor.dashboard.middleware', ['web']);
});

function createUser(): Authenticatable
{
    return (new class extends Authenticatable
    {
        protected $table = 'users';

        protected $guarded = [];
    })->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
}

function setAuthGate(Closure $callback): void
{
    app(HealingFactor::class)->auth($callback);
}

it('returns 403 when gate rejects user', function () {
    $user = createUser();

    setAuthGate(fn ($user) => false);

    $this->actingAs($user)
        ->get('/healing-factor')
        ->assertStatus(403);
});

it('returns 404 when dashboard is disabled', function () {
    config()->set('healing-factor.dashboard.enabled', false);

    $user = createUser();

    $this->actingAs($user)
        ->get('/healing-factor')
        ->assertStatus(404);
});

it('denies access when user is not authenticated', function () {
    $this->getJson('/healing-factor')
        ->assertUnauthorized();
});

it('lists issues when authorized', function () {
    $user = createUser();
    setAuthGate(fn () => true);

    Issue::factory()->count(3)->create();

    $this->actingAs($user)
        ->get('/healing-factor')
        ->assertStatus(200)
        ->assertViewHas('issues')
        ->assertViewHas('statusCounts');
});

it('filters issues by status', function () {
    $user = createUser();
    setAuthGate(fn () => true);

    Issue::factory()->pending()->count(2)->create();
    Issue::factory()->failed()->create();

    $this->actingAs($user)
        ->get('/healing-factor?status=failed')
        ->assertStatus(200)
        ->assertViewHas('issues', function ($issues) {
            return $issues->count() === 1;
        });
});

it('searches issues by title', function () {
    $user = createUser();
    setAuthGate(fn () => true);

    Issue::factory()->create([
        'title' => 'ErrorException: something broke',
        'exception_class' => 'ErrorException',
    ]);
    Issue::factory()->create([
        'title' => 'TypeError: wrong type',
        'exception_class' => 'TypeError',
    ]);

    $this->actingAs($user)
        ->get('/healing-factor?search=ErrorException')
        ->assertStatus(200)
        ->assertViewHas('issues', function ($issues) {
            return $issues->count() === 1;
        });
});

it('shows issue details', function () {
    $user = createUser();
    setAuthGate(fn () => true);

    $issue = Issue::factory()->create([
        'title' => 'ErrorException: test',
        'exception_class' => 'ErrorException',
        'exception_message' => 'test message',
    ]);

    $this->actingAs($user)
        ->get("/healing-factor/{$issue->id}")
        ->assertStatus(200)
        ->assertViewHas('issue')
        ->assertSee('ErrorException')
        ->assertSee('test message');
});

it('displays status count badges', function () {
    $user = createUser();
    setAuthGate(fn () => true);

    Issue::factory()->pending()->count(3)->create();
    Issue::factory()->resolved()->count(2)->create();

    $this->actingAs($user)
        ->get('/healing-factor')
        ->assertStatus(200)
        ->assertViewHas('statusCounts', function ($counts) {
            return $counts->get('pending') === 3
                && $counts->get('resolved') === 2;
        });
});

it('shows pr link only when pr_url is set', function () {
    $user = createUser();
    setAuthGate(fn () => true);

    Issue::factory()->resolved()->create(['pr_url' => 'https://github.com/test/repo/pull/1']);
    Issue::factory()->pending()->create(['pr_url' => null]);

    $this->actingAs($user)
        ->get('/healing-factor')
        ->assertStatus(200)
        ->assertSee('https://github.com/test/repo/pull/1');
});

afterEach(function () {
    (new ReflectionProperty(HealingFactor::class, 'authUsing'))->setValue(null, null);
});

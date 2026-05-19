<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('registers a new user and returns a token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'Sup3rSecret',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect(User::where('email', 'alice@example.com')->exists())->toBeTrue();
});

it('rejects login with bad credentials', function (): void {
    User::factory()->create(['email' => 'bob@example.com', 'password' => 'Sup3rSecret']);

    $this->postJson('/api/auth/login', [
        'email' => 'bob@example.com',
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('logs in with good credentials', function (): void {
    User::factory()->create(['email' => 'bob@example.com', 'password' => 'Sup3rSecret']);

    $this->postJson('/api/auth/login', [
        'email' => 'bob@example.com',
        'password' => 'Sup3rSecret',
    ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
});

it('returns the current user on /me', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('rejects /me without a token', function (): void {
    $this->getJson('/api/me')->assertStatus(401);
});

it('revokes the current token on logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertNoContent();

    // The PAT row should be gone — subsequent requests with that bearer
    // would 401 in production (Laravel's HTTP test harness reuses the
    // application instance and caches the resolved Sanctum user across
    // ->withHeader() calls in the same test, so we assert the DB state
    // directly instead).
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(0);
});

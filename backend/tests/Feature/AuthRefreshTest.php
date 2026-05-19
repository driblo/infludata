<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('issues a new token and revokes the old one on refresh', function (): void {
    $user = User::factory()->create();
    $oldToken = $user->createToken('old')->plainTextToken;

    $resp = $this->withHeader('Authorization', 'Bearer '.$oldToken)
        ->postJson('/api/auth/refresh', ['device_name' => 'flutter'])
        ->assertOk()
        ->assertJsonStructure(['token']);

    $newToken = $resp->json('token');
    expect($newToken)->not->toBe($oldToken);

    // Exactly one PAT for this user: the freshly issued one.
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);
});

it('requires authentication for /api/auth/refresh', function (): void {
    $this->postJson('/api/auth/refresh')->assertStatus(401);
});

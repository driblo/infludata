<?php

declare(strict_types=1);

use App\Models\OauthAccount;
use App\Models\User;
use App\Services\Phyllo\PhylloSdkTokenService;

it('requires auth for /connections', function (): void {
    $this->getJson('/api/connections')->assertStatus(401);
});

it('lists only the authenticated user oauth accounts', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    OauthAccount::create([
        'user_id' => $alice->id, 'network' => 'youtube',
        'phyllo_account_id' => 'a1', 'status' => 'connected',
        'connected_at' => now(),
    ]);
    OauthAccount::create([
        'user_id' => $bob->id, 'network' => 'instagram',
        'phyllo_account_id' => 'b1', 'status' => 'connected',
        'connected_at' => now(),
    ]);

    $this->actingAs($alice, 'sanctum')
        ->getJson('/api/connections')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.network', 'youtube');
});

it('refuses to delete another user oauth account', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $bobsAccount = OauthAccount::create([
        'user_id' => $bob->id, 'network' => 'youtube',
        'phyllo_account_id' => 'b1', 'status' => 'connected',
        'connected_at' => now(),
    ]);

    $this->actingAs($alice, 'sanctum')
        ->deleteJson("/api/connections/{$bobsAccount->id}")
        ->assertStatus(403);
});

it('mints an sdk token via the phyllo service', function (): void {
    $user = User::factory()->create();
    $fake = Mockery::mock(PhylloSdkTokenService::class);
    $fake->shouldReceive('mintTokenFor')->once()->andReturn([
        'sdk_token' => 'tok_abc',
        'expires_at' => now()->addMinutes(30)->toIso8601String(),
        'phyllo_user_id' => 'phy-1',
    ]);
    $this->app->instance(PhylloSdkTokenService::class, $fake);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/connections/phyllo-token')
        ->assertOk()
        ->assertJsonPath('sdk_token', 'tok_abc');
});

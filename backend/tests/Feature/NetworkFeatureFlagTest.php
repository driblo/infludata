<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Config;

it('rejects tracking a disabled network with 422', function (): void {
    Config::set('services.networks.tiktok', false);
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/creators', ['network' => 'tiktok', 'handle' => 'someone'])
        ->assertStatus(422)
        ->assertJsonPath('title', 'NetworkDisabled');
});

it('lets enabled networks through to validation', function (): void {
    Config::set('services.networks.tiktok', true);
    $user = User::factory()->create();

    // Will fail later at the Phyllo lookup since we have no real key,
    // but the network-disabled middleware no longer blocks it. We only
    // assert the 422 isn't the NetworkDisabled one.
    $resp = $this->actingAs($user, 'sanctum')
        ->postJson('/api/creators', ['network' => 'tiktok', 'handle' => 'someone']);

    expect($resp->json('title'))->not->toBe('NetworkDisabled');
});

<?php

declare(strict_types=1);

use App\Jobs\EvaluateAlertsJob;
use App\Models\Alert;
use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Models\User;

it('lists alerts scoped to the user', function (): void {
    $user = User::factory()->create();
    Alert::create([
        'user_id' => $user->id, 'target_type' => 'creator', 'target_id' => 1,
        'kind' => 'follower_milestone', 'threshold' => ['min_followers' => 1000], 'enabled' => true,
    ]);
    $other = User::factory()->create();
    Alert::create([
        'user_id' => $other->id, 'target_type' => 'creator', 'target_id' => 2,
        'kind' => 'follower_milestone', 'threshold' => ['min_followers' => 5], 'enabled' => true,
    ]);

    $this->actingAs($user, 'sanctum')->getJson('/api/alerts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('creates an alert with the user as owner', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')->postJson('/api/alerts', [
        'target_type' => 'creator', 'target_id' => 7, 'kind' => 'follower_milestone',
        'threshold' => ['min_followers' => 100000], 'channel' => 'email',
    ])->assertCreated()->assertJsonPath('user_id', $user->id);
});

it('rejects deleting another user alert', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $alert = Alert::create([
        'user_id' => $bob->id, 'target_type' => 'creator', 'target_id' => 1,
        'kind' => 'follower_milestone', 'threshold' => ['min_followers' => 1], 'enabled' => true,
    ]);

    $this->actingAs($alice, 'sanctum')->deleteJson("/api/alerts/{$alert->id}")
        ->assertStatus(403);
});

it('fires a follower_milestone alert when latest snapshot >= threshold', function (): void {
    $user = User::factory()->create();
    $creator = CreatorProfile::create([
        'network' => 'youtube', 'platform_user_id' => 'a', 'handle' => 'alice', 'fetched_at' => now(),
    ]);
    $alert = Alert::create([
        'user_id' => $user->id, 'target_type' => 'creator', 'target_id' => $creator->id,
        'kind' => 'follower_milestone', 'threshold' => ['min_followers' => 1000], 'enabled' => true,
    ]);
    MetricSnapshot::create([
        'creator_profile_id' => $creator->id, 'network' => 'youtube',
        'captured_at' => now(), 'followers' => 1500,
    ]);

    expect((new EvaluateAlertsJob)->shouldFire($alert))->toBeTrue();
});

it('does NOT fire a follower_milestone alert when snapshot < threshold', function (): void {
    $user = User::factory()->create();
    $creator = CreatorProfile::create([
        'network' => 'youtube', 'platform_user_id' => 'b', 'handle' => 'bob', 'fetched_at' => now(),
    ]);
    $alert = Alert::create([
        'user_id' => $user->id, 'target_type' => 'creator', 'target_id' => $creator->id,
        'kind' => 'follower_milestone', 'threshold' => ['min_followers' => 1000], 'enabled' => true,
    ]);
    MetricSnapshot::create([
        'creator_profile_id' => $creator->id, 'network' => 'youtube',
        'captured_at' => now(), 'followers' => 500,
    ]);

    expect((new EvaluateAlertsJob)->shouldFire($alert))->toBeFalse();
});

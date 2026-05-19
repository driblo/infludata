<?php

declare(strict_types=1);

use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Models\TrackedCreator;
use App\Models\User;

it('returns empty totals when nothing is tracked', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('totals.tracked_count', 0)
        ->assertJsonPath('totals.total_followers', 0);
});

it('computes total followers and top movers across tracked creators', function (): void {
    $user = User::factory()->create();
    $alice = CreatorProfile::create([
        'network' => 'youtube', 'platform_user_id' => 'a', 'handle' => 'alice', 'fetched_at' => now(),
    ]);
    $bob = CreatorProfile::create([
        'network' => 'youtube', 'platform_user_id' => 'b', 'handle' => 'bob', 'fetched_at' => now(),
    ]);
    foreach ([$alice, $bob] as $c) {
        TrackedCreator::create([
            'user_id' => $user->id, 'creator_profile_id' => $c->id,
            'network' => 'youtube', 'handle' => $c->handle, 'added_at' => now(),
        ]);
    }

    // Snapshots: alice gained 5k, bob lost 1k week-over-week.
    MetricSnapshot::create(['creator_profile_id' => $alice->id, 'network' => 'youtube', 'captured_at' => now()->subDays(10), 'followers' => 10000]);
    MetricSnapshot::create(['creator_profile_id' => $alice->id, 'network' => 'youtube', 'captured_at' => now(), 'followers' => 15000]);
    MetricSnapshot::create(['creator_profile_id' => $bob->id, 'network' => 'youtube', 'captured_at' => now()->subDays(10), 'followers' => 5000]);
    MetricSnapshot::create(['creator_profile_id' => $bob->id, 'network' => 'youtube', 'captured_at' => now(), 'followers' => 4000]);

    $resp = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard')->assertOk();
    expect($resp->json('totals.tracked_count'))->toBe(2)
        ->and($resp->json('totals.total_followers'))->toBe(19000)
        ->and($resp->json('top_movers'))->toHaveCount(2);

    $movers = collect($resp->json('top_movers'))->keyBy('creator_profile_id');
    expect($movers[$alice->id]['delta_7d'])->toBe(5000)
        ->and($movers[$bob->id]['delta_7d'])->toBe(-1000);
});

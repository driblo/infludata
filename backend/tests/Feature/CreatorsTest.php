<?php

declare(strict_types=1);

use App\Jobs\BackfillCreatorJob;
use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Models\TrackedCreator;
use App\Models\User;
use App\Services\Ingestion\IdentityResolutionService;
use Illuminate\Support\Facades\Bus;

it('requires auth for /api/creators', function (): void {
    $this->getJson('/api/creators')->assertStatus(401);
});

it('lists only the authenticated user tracked creators', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $creator = CreatorProfile::create([
        'network' => 'youtube',
        'platform_user_id' => 'yt-1',
        'handle' => 'alice-yt',
        'fetched_at' => now(),
    ]);
    TrackedCreator::create([
        'user_id' => $alice->id, 'creator_profile_id' => $creator->id,
        'network' => 'youtube', 'handle' => 'alice-yt', 'added_at' => now(),
    ]);

    $this->actingAs($alice, 'sanctum')->getJson('/api/creators')
        ->assertOk()
        ->assertJsonCount(1, 'data');
    $this->actingAs($bob, 'sanctum')->getJson('/api/creators')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('adds a tracked creator by resolving via the identity service', function (): void {
    Bus::fake();
    $user = User::factory()->create();

    $stub = new class extends IdentityResolutionService
    {
        public function __construct() {}

        public function resolve(string $network, string $handle): CreatorProfile
        {
            return CreatorProfile::updateOrCreate(
                ['network' => $network, 'platform_user_id' => 'yt-42'],
                ['handle' => $handle, 'follower_count' => 1000, 'fetched_at' => now()],
            );
        }
    };
    $this->app->instance(IdentityResolutionService::class, $stub);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/creators', ['network' => 'youtube', 'handle' => 'mrbeast'])
        ->assertCreated()
        ->assertJsonPath('creator_profile.handle', 'mrbeast')
        ->assertJsonPath('tracked_creator.network', 'youtube');

    Bus::assertDispatched(BackfillCreatorJob::class);
});

it('rejects an unknown network', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/creators', ['network' => 'snapchat', 'handle' => 'x'])
        ->assertStatus(422);
});

it('returns 403 reading a profile not tracked by the user', function (): void {
    $user = User::factory()->create();
    $creator = CreatorProfile::create([
        'network' => 'youtube',
        'platform_user_id' => 'yt-99',
        'handle' => 'somebody',
        'fetched_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/creators/{$creator->id}/profile")
        ->assertStatus(403);
});

it('returns timeseries metrics scoped to the requested range', function (): void {
    $user = User::factory()->create();
    $creator = CreatorProfile::create([
        'network' => 'youtube',
        'platform_user_id' => 'yt-1',
        'handle' => 'alice',
        'fetched_at' => now(),
    ]);
    TrackedCreator::create([
        'user_id' => $user->id, 'creator_profile_id' => $creator->id,
        'network' => 'youtube', 'handle' => 'alice', 'added_at' => now(),
    ]);

    foreach ([1, 7, 40] as $daysAgo) {
        MetricSnapshot::create([
            'creator_profile_id' => $creator->id,
            'network' => 'youtube',
            'captured_at' => now()->subDays($daysAgo),
            'followers' => 1000 + $daysAgo,
        ]);
    }

    $resp = $this->actingAs($user, 'sanctum')
        ->getJson("/api/creators/{$creator->id}/metrics?range=30d")
        ->assertOk();

    expect($resp->json('data'))->toHaveCount(2)
        ->and($resp->json('range'))->toBe('30d');
});

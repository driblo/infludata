<?php

declare(strict_types=1);

use App\Jobs\EvaluateAlertsJob;
use App\Models\Alert;
use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Models\User;
use App\Notifications\AlertFiredNotification;
use Illuminate\Support\Facades\Notification;

it('sends an AlertFiredNotification when a follower_milestone alert fires', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $creator = CreatorProfile::factory()->create(['network' => 'youtube']);
    MetricSnapshot::factory()->create([
        'creator_profile_id' => $creator->id,
        'network' => 'youtube',
        'followers' => 250_000,
        'captured_at' => now(),
    ]);
    Alert::factory()->create([
        'user_id' => $user->id,
        'target_id' => $creator->id,
        'kind' => 'follower_milestone',
        'threshold' => ['min_followers' => 100_000],
        'enabled' => true,
    ]);

    (new EvaluateAlertsJob)->handle();

    Notification::assertSentTo($user, AlertFiredNotification::class);
});

it('does NOT send a notification when the threshold is not met', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $creator = CreatorProfile::factory()->create(['network' => 'youtube']);
    MetricSnapshot::factory()->create([
        'creator_profile_id' => $creator->id,
        'network' => 'youtube',
        'followers' => 500,
        'captured_at' => now(),
    ]);
    Alert::factory()->create([
        'user_id' => $user->id,
        'target_id' => $creator->id,
        'kind' => 'follower_milestone',
        'threshold' => ['min_followers' => 100_000],
        'enabled' => true,
    ]);

    (new EvaluateAlertsJob)->handle();

    Notification::assertNothingSent();
});

it('skips disabled alerts even when they would otherwise fire', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $creator = CreatorProfile::factory()->create();
    MetricSnapshot::factory()->create([
        'creator_profile_id' => $creator->id,
        'followers' => 250_000,
    ]);
    Alert::factory()->create([
        'user_id' => $user->id,
        'target_id' => $creator->id,
        'kind' => 'follower_milestone',
        'threshold' => ['min_followers' => 100_000],
        'enabled' => false,
    ]);

    (new EvaluateAlertsJob)->handle();

    Notification::assertNothingSent();
});

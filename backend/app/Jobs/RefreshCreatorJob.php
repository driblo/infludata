<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Services\Ingestion\IdentityResolutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Lightweight periodic refresh for a tracked public creator: re-fetch the
 * profile snapshot via Phyllo identity lookup and record a metric snapshot
 * timeseries row. Cheaper than a full BackfillCreatorJob.
 */
class RefreshCreatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 600, 3600];

    public function __construct(public readonly int $creatorProfileId) {}

    public function uniqueId(): string
    {
        return 'creator-refresh:'.$this->creatorProfileId;
    }

    public function handle(IdentityResolutionService $identity): void
    {
        $creator = CreatorProfile::find($this->creatorProfileId);
        if ($creator === null) {
            return;
        }

        // Re-resolve to pull the latest reputation counts. Same handle, so
        // the unique index dedupes; updateOrCreate refreshes follower counts.
        $refreshed = $identity->resolve($creator->network, $creator->handle);

        MetricSnapshot::create([
            'creator_profile_id' => $refreshed->getKey(),
            'network' => $refreshed->network,
            'captured_at' => now(),
            'followers' => $refreshed->follower_count,
            'following' => $refreshed->following_count,
            'source' => 'phyllo',
            'raw' => $refreshed->raw_payload ?? [],
        ]);
    }
}

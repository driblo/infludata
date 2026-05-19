<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ApiJob;
use App\Models\ContentItem;
use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Services\Phyllo\PhylloClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Initial backfill for a tracked public creator: refresh profile snapshot
 * and pull recent content items. Demographics are own-account-only so we
 * don't fetch them here.
 */
class BackfillCreatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 3600, 21600];

    public function __construct(public readonly int $creatorProfileId) {}

    public function uniqueId(): string
    {
        return 'creator-backfill:'.$this->creatorProfileId;
    }

    public function handle(PhylloClient $phyllo): void
    {
        $creator = CreatorProfile::find($this->creatorProfileId);
        if ($creator === null) {
            return;
        }

        $idempotencyKey = sha1(self::class.':'.$this->creatorProfileId);
        $apiJob = ApiJob::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'job_class' => self::class,
                'payload' => ['creator_profile_id' => $this->creatorProfileId],
                'status' => 'running',
                'started_at' => now(),
            ],
        );

        try {
            MetricSnapshot::create([
                'creator_profile_id' => $creator->getKey(),
                'network' => $creator->network,
                'captured_at' => now(),
                'followers' => $creator->follower_count,
                'following' => $creator->following_count,
                'source' => 'phyllo',
                'raw' => $creator->raw_payload ?? [],
            ]);

            // Phyllo public-creator content endpoints require an account or
            // identity-id reference; we use the platform_user_id we already
            // stored. Implementations may differ per work_platform — this
            // path is best-effort and falls through cleanly if empty.
            $contents = $phyllo->listContents($creator->platform_user_id, limit: 50);
            $this->upsertContents($creator, (array) ($contents['data'] ?? []));

            $apiJob->update(['status' => 'success', 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $apiJob->update([
                'status' => 'failed',
                'attempts' => ($apiJob->attempts ?? 0) + 1,
                'error' => substr($e->getMessage(), 0, 1000),
            ]);
            throw $e;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function upsertContents(CreatorProfile $creator, array $items): void
    {
        DB::transaction(function () use ($creator, $items): void {
            foreach ($items as $item) {
                ContentItem::updateOrCreate(
                    [
                        'network' => $creator->network,
                        'external_id' => (string) ($item['external_id'] ?? $item['id'] ?? ''),
                    ],
                    [
                        'creator_profile_id' => $creator->getKey(),
                        'kind' => (string) ($item['type'] ?? 'unknown'),
                        'title' => $item['title'] ?? null,
                        'caption' => $item['description'] ?? null,
                        'url' => $item['url'] ?? null,
                        'thumbnail_url' => $item['thumbnail_url'] ?? null,
                        'duration_s' => isset($item['duration']) ? (int) $item['duration'] : null,
                        'published_at' => $item['published_at'] ?? null,
                        'raw' => $item,
                    ],
                );
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ApiJob;
use App\Models\AudienceDemographic;
use App\Models\ContentItem;
use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Models\OauthAccount;
use App\Services\Phyllo\PhylloClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Initial backfill for a newly connected own-account: profile snapshot,
 * recent content + their metrics, audience demographics.
 *
 * Idempotent — uses an api_jobs row keyed by `phyllo_account_id`.
 */
class BackfillOwnAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 3600, 21600];

    public function __construct(public readonly string $phylloAccountId) {}

    public function uniqueId(): string
    {
        return 'backfill:'.$this->phylloAccountId;
    }

    public function handle(PhylloClient $phyllo): void
    {
        $account = OauthAccount::where('phyllo_account_id', $this->phylloAccountId)->first();
        if ($account === null) {
            Log::warning('backfill.no_oauth_account', ['phyllo_account_id' => $this->phylloAccountId]);

            return;
        }

        $idempotencyKey = sha1(self::class.':'.$this->phylloAccountId);
        $apiJob = ApiJob::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'job_class' => self::class,
                'payload' => ['phyllo_account_id' => $this->phylloAccountId],
                'status' => 'running',
                'started_at' => now(),
                'oauth_account_id' => $account->getKey(),
            ],
        );

        try {
            $profile = $phyllo->getProfile($this->phylloAccountId);
            $creator = $this->upsertProfile($account->network, $profile);

            $this->recordSnapshot($creator, $profile);

            $contents = $phyllo->listContents($this->phylloAccountId, limit: 100);
            $this->upsertContents($creator, $account->network, (array) ($contents['data'] ?? []));

            $audience = $phyllo->getAudience($this->phylloAccountId);
            $this->upsertAudience($account, $audience);

            $account->update(['last_synced_at' => now()]);
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
     * @param  array<string, mixed>  $profile
     */
    private function upsertProfile(string $network, array $profile): CreatorProfile
    {
        $platformUserId = (string) ($profile['platform_user_id'] ?? $profile['id'] ?? '');

        return CreatorProfile::updateOrCreate(
            ['network' => $network, 'platform_user_id' => $platformUserId],
            [
                'handle' => (string) ($profile['username'] ?? $profile['handle'] ?? ''),
                'display_name' => $profile['full_name'] ?? $profile['display_name'] ?? null,
                'avatar_url' => $profile['image_url'] ?? null,
                'bio' => $profile['introduction'] ?? $profile['bio'] ?? null,
                'follower_count' => (int) ($profile['reputation']['follower_count'] ?? $profile['follower_count'] ?? 0),
                'following_count' => (int) ($profile['reputation']['following_count'] ?? $profile['following_count'] ?? 0),
                'is_verified' => (bool) ($profile['is_verified'] ?? false),
                'country' => $profile['country'] ?? null,
                'raw_payload' => $profile,
                'fetched_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function recordSnapshot(CreatorProfile $creator, array $profile): void
    {
        MetricSnapshot::create([
            'creator_profile_id' => $creator->getKey(),
            'network' => $creator->network,
            'captured_at' => now(),
            'followers' => $creator->follower_count,
            'following' => $creator->following_count,
            'posts_count' => (int) ($profile['reputation']['content_count'] ?? 0),
            'total_likes' => (int) ($profile['reputation']['like_count'] ?? 0),
            'total_views' => (int) ($profile['reputation']['view_count'] ?? 0),
            'engagement_rate' => $profile['engagement_rate'] ?? null,
            'source' => 'phyllo',
            'raw' => $profile,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function upsertContents(CreatorProfile $creator, string $network, array $items): void
    {
        DB::transaction(function () use ($creator, $network, $items): void {
            foreach ($items as $item) {
                ContentItem::updateOrCreate(
                    [
                        'network' => $network,
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

    /**
     * @param  array<string, mixed>  $audience
     */
    private function upsertAudience(OauthAccount $account, array $audience): void
    {
        /** @var list<array{dimension:string, bucket:string, value:float}> $buckets */
        $buckets = [];

        foreach ((array) ($audience['gender_age_distribution'] ?? []) as $row) {
            $buckets[] = [
                'dimension' => 'gender_age',
                'bucket' => sprintf('%s:%s', $row['gender'] ?? '?', $row['age_range'] ?? '?'),
                'value' => (float) ($row['value'] ?? 0),
            ];
        }
        foreach ((array) ($audience['country_distribution'] ?? []) as $row) {
            $buckets[] = [
                'dimension' => 'country',
                'bucket' => (string) ($row['code'] ?? '?'),
                'value' => (float) ($row['value'] ?? 0),
            ];
        }

        $now = now();
        foreach ($buckets as $b) {
            AudienceDemographic::create([
                'oauth_account_id' => $account->getKey(),
                'captured_at' => $now,
                'dimension' => $b['dimension'],
                'bucket' => $b['bucket'],
                'value_pct' => $b['value'],
                'raw' => $audience,
            ]);
        }
    }
}

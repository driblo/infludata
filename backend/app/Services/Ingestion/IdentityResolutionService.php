<?php

declare(strict_types=1);

namespace App\Services\Ingestion;

use App\Models\CreatorProfile;
use App\Services\Phyllo\PhylloClient;
use RuntimeException;

/**
 * Resolve a (network, handle) pair into a CreatorProfile row.
 *
 * Uses Phyllo's identity search to get the platform_user_id and an initial
 * profile snapshot, then upserts into creator_profiles. Subsequent calls
 * with the same handle are deduplicated via the (network, platform_user_id)
 * unique index.
 */
class IdentityResolutionService
{
    public function __construct(private readonly PhylloClient $phyllo) {}

    public function resolve(string $network, string $handle): CreatorProfile
    {
        $handle = ltrim(trim($handle), '@');
        if ($handle === '') {
            throw new RuntimeException('handle is empty');
        }

        $result = $this->phyllo->getIdentityByHandle($network, $handle);
        $profile = $this->firstProfile($result);

        $platformUserId = (string) ($profile['platform_user_id'] ?? $profile['id'] ?? '');
        if ($platformUserId === '') {
            throw new RuntimeException(
                sprintf('Could not resolve %s/@%s via Phyllo identity lookup.', $network, $handle),
            );
        }

        return CreatorProfile::updateOrCreate(
            ['network' => $network, 'platform_user_id' => $platformUserId],
            [
                'handle' => (string) ($profile['username'] ?? $handle),
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
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function firstProfile(array $result): array
    {
        if (isset($result['data']) && is_array($result['data']) && array_is_list($result['data'])) {
            $first = $result['data'][0] ?? null;
            if (is_array($first)) {
                /** @var array<string, mixed> $first */
                return $first;
            }
        }

        return $result;
    }
}

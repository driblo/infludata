<?php

declare(strict_types=1);

namespace App\Services\Platforms\Facebook;

/**
 * Allowlist of Facebook Page Insights metrics that remain usable.
 *
 * Several legacy metrics are deprecated for all API versions by Meta on
 * June 15, 2026. To avoid hard breaks we only ever request from this
 * curated list; anything outside it is rejected by the client wrapper.
 *
 * Source: developers.facebook.com/docs/graph-api/reference/insights (as
 * of May 2026 post-deprecation list).
 */
final class FacebookMetricsRegistry
{
    /** @var list<string> */
    private const ACTIVE = [
        // Reach / impressions (post 2026 deprecation, the lifetime variants are gone
        // but per-period values remain).
        'page_impressions_unique',
        'page_impressions',
        'page_post_engagements',
        'page_views_total',
        'page_fans',
        'page_fan_adds_unique',
        'page_fan_removes_unique',
        // Per-post insights (still supported as of v21+).
        'post_impressions',
        'post_impressions_unique',
        'post_reactions_by_type_total',
        'post_clicks',
    ];

    /** @var list<string> */
    private const DEPRECATED = [
        'page_video_views',          // gone for non-Reels content
        'page_consumptions',
        'page_negative_feedback',
        'page_actions_post_reactions_total',
    ];

    /** @return list<string> */
    public static function active(): array
    {
        return self::ACTIVE;
    }

    public static function isActive(string $metric): bool
    {
        return in_array($metric, self::ACTIVE, true);
    }

    public static function isDeprecated(string $metric): bool
    {
        return in_array($metric, self::DEPRECATED, true);
    }

    /**
     * @param  list<string>  $requested
     * @return list<string>
     */
    public static function filter(array $requested): array
    {
        return array_values(array_filter($requested, self::isActive(...)));
    }
}

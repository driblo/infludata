<?php

declare(strict_types=1);

use App\Services\Platforms\Facebook\FacebookMetricsRegistry;

it('reports the active and deprecated lists', function (): void {
    expect(FacebookMetricsRegistry::isActive('page_impressions_unique'))->toBeTrue()
        ->and(FacebookMetricsRegistry::isActive('page_video_views'))->toBeFalse()
        ->and(FacebookMetricsRegistry::isDeprecated('page_video_views'))->toBeTrue();
});

it('filters out deprecated metrics from a request', function (): void {
    $requested = [
        'page_impressions',
        'page_video_views',     // deprecated June 15, 2026
        'page_consumptions',    // deprecated
        'post_clicks',
    ];

    expect(FacebookMetricsRegistry::filter($requested))
        ->toBe(['page_impressions', 'post_clicks']);
});

it('returns an empty list when every requested metric is deprecated', function (): void {
    expect(FacebookMetricsRegistry::filter(['page_consumptions', 'page_negative_feedback']))
        ->toBe([]);
});

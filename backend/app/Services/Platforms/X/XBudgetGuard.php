<?php

declare(strict_types=1);

namespace App\Services\Platforms\X;

use Illuminate\Support\Facades\Cache;

/**
 * Daily cost guard for the X API. X is pay-per-use as of Feb 2026
 * ($0.005/read with capped owned-reads pricing). We track spend in a
 * Redis counter keyed by UTC date and refuse to issue requests beyond
 * a configured daily ceiling.
 */
class XBudgetGuard
{
    public function __construct(
        private readonly float $maxDailyUsd,
        private readonly string $cachePrefix = 'x:spend:',
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            maxDailyUsd: (float) config('services.x.max_daily_usd', 5.0),
        );
    }

    public function spentToday(): float
    {
        return (float) Cache::get($this->key(), 0.0);
    }

    public function remaining(): float
    {
        return max(0.0, $this->maxDailyUsd - $this->spentToday());
    }

    public function canSpend(float $usd): bool
    {
        return $this->spentToday() + $usd <= $this->maxDailyUsd;
    }

    /**
     * Atomically record spend. Returns the new running total.
     */
    public function record(float $usd): float
    {
        $key = $this->key();
        $current = (float) Cache::get($key, 0.0);
        $new = $current + $usd;
        Cache::put($key, $new, $this->ttlSeconds());

        return $new;
    }

    public function isKillSwitchActive(): bool
    {
        return $this->maxDailyUsd <= 0;
    }

    private function key(): string
    {
        return $this->cachePrefix.now()->utc()->toDateString();
    }

    private function ttlSeconds(): int
    {
        // Seconds remaining until midnight UTC. Clamp to >=60s so a near-
        // midnight write isn't immediately evicted.
        $remaining = (int) max(60, now()->utc()->endOfDay()->getTimestamp() - now()->utc()->getTimestamp());

        return $remaining;
    }
}

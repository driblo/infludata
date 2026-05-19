<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Alert;
use App\Models\MetricSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Evaluate every enabled alert. Designed to be cheap — single pass over
 * Alert rows joined to the latest MetricSnapshot per creator. Hourly.
 */
class EvaluateAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $fired = 0;
        Alert::query()
            ->where('enabled', true)
            ->chunkById(200, function ($alerts) use (&$fired): void {
                foreach ($alerts as $alert) {
                    if ($this->shouldFire($alert)) {
                        $this->fire($alert);
                        $fired++;
                    }
                }
            });

        Log::info('alerts.evaluated', ['fired' => $fired]);
    }

    public function shouldFire(Alert $alert): bool
    {
        return match ($alert->kind) {
            'follower_milestone' => $this->followerMilestone($alert),
            'engagement_drop' => $this->engagementDrop($alert),
            'new_content' => false, // wired through webhook handler instead
            default => false,
        };
    }

    private function followerMilestone(Alert $alert): bool
    {
        $threshold = (int) ($alert->threshold['min_followers'] ?? 0);
        if ($threshold <= 0) {
            return false;
        }

        $latest = MetricSnapshot::query()
            ->where('creator_profile_id', $alert->target_id)
            ->orderByDesc('captured_at')
            ->value('followers');

        return (int) $latest >= $threshold;
    }

    private function engagementDrop(Alert $alert): bool
    {
        $dropPct = (float) ($alert->threshold['drop_pct'] ?? 0);
        if ($dropPct <= 0) {
            return false;
        }

        $snapshots = MetricSnapshot::query()
            ->where('creator_profile_id', $alert->target_id)
            ->orderByDesc('captured_at')
            ->limit(2)
            ->get(['engagement_rate']);

        if ($snapshots->count() < 2) {
            return false;
        }

        $now = (float) ($snapshots[0]->engagement_rate ?? 0);
        $prev = (float) ($snapshots[1]->engagement_rate ?? 0);
        if ($prev <= 0) {
            return false;
        }

        return (($prev - $now) / $prev) * 100 >= $dropPct;
    }

    private function fire(Alert $alert): void
    {
        $alert->update(['last_fired_at' => now()]);
        // Email + push delivery wiring lives in a follow-up patch; this job
        // is intentionally side-effect light to keep the unit-test loop
        // synchronous and fast.
        Log::info('alerts.fired', [
            'alert_id' => $alert->id,
            'kind' => $alert->kind,
            'user_id' => $alert->user_id,
        ]);
    }
}

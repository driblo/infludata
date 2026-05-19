<?php

declare(strict_types=1);

use App\Jobs\EvaluateAlertsJob;
use App\Jobs\RefreshCreatorJob;
use App\Models\TrackedCreator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Refresh tracked creators on their configured cadence. Default 1440 minutes
// (daily). The query walks rows in chunks, dispatching one job each, with
// a jitter so we don't slam the upstream at 04:00 sharp.
Schedule::call(function (): void {
    TrackedCreator::query()
        ->select('creator_profile_id', 'refresh_cadence_minutes')
        ->groupBy('creator_profile_id', 'refresh_cadence_minutes')
        ->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $jitter = crc32((string) $row->creator_profile_id) % 600;
                RefreshCreatorJob::dispatch((int) $row->creator_profile_id)
                    ->delay(now()->addSeconds($jitter));
            }
        }, 'creator_profile_id');
})->dailyAt('04:00')->name('refresh-tracked-creators')->withoutOverlapping();

Schedule::job(new EvaluateAlertsJob)->hourly()->name('evaluate-alerts')->withoutOverlapping();

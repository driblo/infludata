<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExportRequest;
use App\Models\OauthAccount;
use App\Models\TrackedCreator;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Build a CSV/JSON export (or a GDPR data dump) for a user, write it to
 * the local disk, and stamp the ExportRequest with the file URL.
 */
class BuildExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $exportRequestId) {}

    public function handle(): void
    {
        $export = ExportRequest::find($this->exportRequestId);
        if ($export === null) {
            return;
        }

        $export->update(['status' => 'running']);

        $user = User::find($export->user_id);
        if ($user === null) {
            $export->update(['status' => 'failed', 'completed_at' => now()]);

            return;
        }

        $payload = match ($export->kind) {
            'gdpr' => $this->gdpr($user),
            'json' => $this->summary($user),
            'csv' => $this->summaryCsv($user),
            default => '',
        };

        $ext = $export->kind === 'csv' ? 'csv' : 'json';
        $path = sprintf('exports/%d/%d.%s', $user->id, $export->id, $ext);
        Storage::disk('local')->put($path, $payload);

        $export->update([
            'status' => 'completed',
            'file_url' => Storage::disk('local')->path($path),
            'completed_at' => now(),
        ]);
    }

    private function gdpr(User $user): string
    {
        return (string) json_encode([
            'user' => $user->only(['id', 'name', 'email', 'created_at']),
            'oauth_accounts' => OauthAccount::where('user_id', $user->id)->get()->toArray(),
            'tracked_creators' => TrackedCreator::where('user_id', $user->id)->get()->toArray(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    private function summary(User $user): string
    {
        return (string) json_encode([
            'tracked_count' => TrackedCreator::where('user_id', $user->id)->count(),
            'connections' => OauthAccount::where('user_id', $user->id)->count(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    private function summaryCsv(User $user): string
    {
        $rows = TrackedCreator::query()
            ->where('user_id', $user->id)
            ->get(['id', 'network', 'handle', 'added_at']);
        $lines = ['id,network,handle,added_at'];
        foreach ($rows as $r) {
            $lines[] = sprintf('%d,%s,%s,%s', $r->id, $r->network, $r->handle, $r->added_at?->toIso8601String() ?? '');
        }

        return implode("\n", $lines)."\n";
    }
}

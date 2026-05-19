<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetricSnapshot;
use App\Models\TrackedCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->getKey();
        $trackedIds = TrackedCreator::query()
            ->where('user_id', $userId)
            ->pluck('creator_profile_id');

        if ($trackedIds->isEmpty()) {
            return response()->json([
                'totals' => ['tracked_count' => 0, 'total_followers' => 0],
                'top_movers' => [],
            ]);
        }

        $latest = MetricSnapshot::query()
            ->whereIn('creator_profile_id', $trackedIds)
            ->select(['creator_profile_id', DB::raw('MAX(captured_at) as latest_at')])
            ->groupBy('creator_profile_id')
            ->get()
            ->keyBy('creator_profile_id');

        $latestRows = MetricSnapshot::query()
            ->whereIn('creator_profile_id', $trackedIds)
            ->whereIn('captured_at', $latest->pluck('latest_at'))
            ->get(['creator_profile_id', 'followers', 'captured_at']);

        $weekAgo = MetricSnapshot::query()
            ->whereIn('creator_profile_id', $trackedIds)
            ->where('captured_at', '<=', now()->subDays(7))
            ->orderByDesc('captured_at')
            ->get(['creator_profile_id', 'followers'])
            ->unique('creator_profile_id')
            ->keyBy('creator_profile_id');

        $movers = $latestRows
            ->map(function ($latest) use ($weekAgo): array {
                $prior = $weekAgo[$latest->creator_profile_id] ?? null;
                $delta = $prior !== null ? $latest->followers - $prior->followers : 0;

                return [
                    'creator_profile_id' => $latest->creator_profile_id,
                    'followers' => $latest->followers,
                    'delta_7d' => $delta,
                ];
            })
            ->sortByDesc(fn (array $row): int => abs($row['delta_7d']))
            ->take(10)
            ->values();

        return response()->json([
            'totals' => [
                'tracked_count' => $trackedIds->count(),
                'total_followers' => $latestRows->sum('followers'),
            ],
            'top_movers' => $movers,
        ]);
    }
}

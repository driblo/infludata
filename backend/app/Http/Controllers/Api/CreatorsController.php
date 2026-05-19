<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Creators\StoreCreatorRequest;
use App\Jobs\BackfillCreatorJob;
use App\Models\ContentItem;
use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use App\Models\TrackedCreator;
use App\Services\Ingestion\IdentityResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->getKey();
        $tracked = TrackedCreator::query()
            ->with('creatorProfile:id,network,handle,display_name,avatar_url,follower_count,is_verified')
            ->where('user_id', $userId)
            ->orderByDesc('added_at')
            ->get(['id', 'creator_profile_id', 'network', 'handle', 'label', 'refresh_cadence_minutes', 'added_at']);

        return response()->json(['data' => $tracked]);
    }

    public function store(StoreCreatorRequest $request, IdentityResolutionService $identity): JsonResponse
    {
        /** @var array{network:string, handle:string, label?:?string} $data */
        $data = $request->validated();

        $creator = $identity->resolve($data['network'], $data['handle']);

        $tracked = TrackedCreator::updateOrCreate(
            [
                'user_id' => $request->user()?->getKey(),
                'creator_profile_id' => $creator->getKey(),
            ],
            [
                'network' => $creator->network,
                'handle' => $creator->handle,
                'label' => $data['label'] ?? null,
                'added_at' => now(),
            ],
        );

        BackfillCreatorJob::dispatch($creator->getKey())->onQueue('default');

        return response()->json([
            'tracked_creator' => $tracked,
            'creator_profile' => $creator,
        ], 201);
    }

    public function show(Request $request, CreatorProfile $creator): JsonResponse
    {
        $this->assertTracked($request, $creator);

        $latest = MetricSnapshot::query()
            ->where('creator_profile_id', $creator->getKey())
            ->orderByDesc('captured_at')
            ->limit(1)
            ->get();

        return response()->json([
            'profile' => $creator,
            'latest_snapshot' => $latest->first(),
        ]);
    }

    public function metrics(Request $request, CreatorProfile $creator): JsonResponse
    {
        $this->assertTracked($request, $creator);

        $request->validate([
            'range' => ['nullable', 'in:7d,30d,90d,1y'],
        ]);
        $range = (string) $request->query('range', '30d');
        $days = match ($range) {
            '7d' => 7,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $rows = MetricSnapshot::query()
            ->where('creator_profile_id', $creator->getKey())
            ->where('captured_at', '>=', now()->subDays($days))
            ->orderBy('captured_at')
            ->get(['captured_at', 'followers', 'following', 'total_likes', 'total_views', 'engagement_rate']);

        return response()->json(['data' => $rows, 'range' => $range]);
    }

    public function content(Request $request, CreatorProfile $creator): JsonResponse
    {
        $this->assertTracked($request, $creator);

        $items = ContentItem::query()
            ->where('creator_profile_id', $creator->getKey())
            ->orderByDesc('published_at')
            ->limit(50)
            ->get(['id', 'kind', 'title', 'url', 'thumbnail_url', 'duration_s', 'published_at']);

        return response()->json(['data' => $items]);
    }

    public function destroy(Request $request, TrackedCreator $creator): JsonResponse
    {
        abort_unless($creator->user_id === $request->user()?->getKey(), 403);
        $creator->delete();

        return response()->json(status: 204);
    }

    private function assertTracked(Request $request, CreatorProfile $creator): void
    {
        $exists = TrackedCreator::query()
            ->where('user_id', $request->user()?->getKey())
            ->where('creator_profile_id', $creator->getKey())
            ->exists();
        abort_unless($exists, 403);
    }
}

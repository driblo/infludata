<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AudienceDemographic;
use App\Models\OauthAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function show(Request $request, OauthAccount $account): JsonResponse
    {
        abort_unless($account->user_id === $request->user()?->getKey(), 403);

        $rows = AudienceDemographic::query()
            ->where('oauth_account_id', $account->getKey())
            ->orderByDesc('captured_at')
            ->limit(500)
            ->get(['dimension', 'bucket', 'value_pct', 'captured_at']);

        // Latest snapshot wins per (dimension, bucket).
        $latestByDim = [];
        foreach ($rows as $r) {
            $key = $r->dimension.'|'.$r->bucket;
            if (! isset($latestByDim[$key])) {
                $latestByDim[$key] = $r;
            }
        }

        $grouped = [];
        foreach ($latestByDim as $r) {
            $grouped[$r->dimension][] = [
                'bucket' => $r->bucket,
                'value_pct' => (float) $r->value_pct,
            ];
        }

        return response()->json(['data' => $grouped]);
    }
}

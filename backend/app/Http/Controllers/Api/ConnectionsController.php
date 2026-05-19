<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OauthAccount;
use App\Services\Phyllo\PhylloSdkTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = OauthAccount::query()
            ->where('user_id', $request->user()?->getKey())
            ->orderByDesc('connected_at')
            ->get(['id', 'network', 'external_handle', 'status', 'connected_at', 'last_synced_at']);

        return response()->json(['data' => $accounts]);
    }

    public function phylloToken(Request $request, PhylloSdkTokenService $tokens): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        return response()->json($tokens->mintTokenFor($user));
    }

    public function destroy(Request $request, OauthAccount $connection): JsonResponse
    {
        abort_unless($connection->user_id === $request->user()?->getKey(), 403);

        $connection->update(['status' => 'revoked']);
        $connection->delete();

        return response()->json(status: 204);
    }
}

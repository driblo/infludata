<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alerts\StoreAlertRequest;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::query()
            ->where('user_id', $request->user()?->getKey())
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $alerts]);
    }

    public function store(StoreAlertRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $alert = Alert::create([
            'user_id' => $request->user()?->getKey(),
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'kind' => $data['kind'],
            'threshold' => $data['threshold'],
            'channel' => $data['channel'] ?? 'email',
            'enabled' => $data['enabled'] ?? true,
        ]);

        return response()->json($alert, 201);
    }

    public function destroy(Request $request, Alert $alert): JsonResponse
    {
        abort_unless($alert->user_id === $request->user()?->getKey(), 403);
        $alert->delete();

        return response()->json(status: 204);
    }
}

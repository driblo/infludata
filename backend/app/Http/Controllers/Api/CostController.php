<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platforms\X\XBudgetGuard;
use Illuminate\Http\JsonResponse;

class CostController extends Controller
{
    public function index(XBudgetGuard $xBudget): JsonResponse
    {
        return response()->json([
            'data' => [
                'x' => [
                    'spent_today_usd' => round($xBudget->spentToday(), 4),
                    'remaining_today_usd' => round($xBudget->remaining(), 4),
                    'kill_switch' => $xBudget->isKillSwitchActive(),
                ],
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'app' => 'ok',
            'db' => $this->check(fn () => DB::connection()->getPdo() !== null),
            'redis' => $this->check(fn () => Redis::ping() !== false),
        ];

        $allOk = ! in_array('error', $checks, true);

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'version' => config('app.version', 'dev'),
        ], 200);
    }

    private function check(callable $probe): string
    {
        try {
            return $probe() ? 'ok' : 'error';
        } catch (Throwable) {
            return 'error';
        }
    }
}

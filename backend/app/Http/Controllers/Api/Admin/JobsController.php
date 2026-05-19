<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless((bool) ($request->user()?->getAttribute('is_admin') ?? false), 403);

        $jobs = ApiJob::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'job_class', 'status', 'started_at', 'finished_at', 'attempts', 'error']);

        return response()->json(['data' => $jobs]);
    }
}

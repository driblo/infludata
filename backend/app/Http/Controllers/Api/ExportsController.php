<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BuildExportJob;
use App\Models\ExportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExportsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['csv', 'json', 'gdpr'])],
        ]);

        $export = ExportRequest::create([
            'user_id' => $request->user()?->getKey(),
            'kind' => $data['kind'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        BuildExportJob::dispatch($export->id);

        return response()->json($export, 202);
    }

    public function show(Request $request, ExportRequest $export): JsonResponse
    {
        abort_unless($export->user_id === $request->user()?->getKey(), 403);

        return response()->json($export);
    }
}

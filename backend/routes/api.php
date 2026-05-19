<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index'])->name('api.health');

Route::prefix('auth')->group(function (): void {
    // Placeholder routes — implemented in M1.
    Route::post('/login', fn () => response()->json(['detail' => 'not implemented'], 501));
    Route::post('/register', fn () => response()->json(['detail' => 'not implemented'], 501));
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', fn () => response()->json(auth()->user()));
});

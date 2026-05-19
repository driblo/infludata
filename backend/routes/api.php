<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ConnectionsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Webhooks\PhylloWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index'])->name('api.health');

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
});

Route::post('/webhooks/phyllo', PhylloWebhookController::class)->name('api.webhooks.phyllo');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    Route::get('/connections', [ConnectionsController::class, 'index'])->name('api.connections.index');
    Route::post('/connections/phyllo-token', [ConnectionsController::class, 'phylloToken'])->name('api.connections.phyllo-token');
    Route::delete('/connections/{connection}', [ConnectionsController::class, 'destroy'])->name('api.connections.destroy');
});

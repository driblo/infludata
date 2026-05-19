<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\JobsController as AdminJobsController;
use App\Http\Controllers\Api\AlertsController;
use App\Http\Controllers\Api\AudienceController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ConnectionsController;
use App\Http\Controllers\Api\CostController;
use App\Http\Controllers\Api\CreatorsController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Webhooks\PhylloWebhookController;
use App\Http\Middleware\EnsureNetworkEnabled;
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

    Route::get('/creators', [CreatorsController::class, 'index'])->name('api.creators.index');
    Route::post('/creators', [CreatorsController::class, 'store'])
        ->middleware(['throttle:write', EnsureNetworkEnabled::class])
        ->name('api.creators.store');
    Route::delete('/creators/{creator}', [CreatorsController::class, 'destroy'])->name('api.creators.destroy');
    Route::get('/creators/{creator}/profile', [CreatorsController::class, 'show'])->name('api.creators.show');
    Route::get('/creators/{creator}/metrics', [CreatorsController::class, 'metrics'])->name('api.creators.metrics');
    Route::get('/creators/{creator}/content', [CreatorsController::class, 'content'])->name('api.creators.content');

    Route::get('/cost', [CostController::class, 'index'])->name('api.cost.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard.index');
    Route::get('/audience/{account}', [AudienceController::class, 'show'])->name('api.audience.show');

    Route::get('/alerts', [AlertsController::class, 'index'])->name('api.alerts.index');
    Route::post('/alerts', [AlertsController::class, 'store'])->name('api.alerts.store');
    Route::delete('/alerts/{alert}', [AlertsController::class, 'destroy'])->name('api.alerts.destroy');

    Route::post('/exports', [ExportsController::class, 'store'])->name('api.exports.store');
    Route::get('/exports/{export}', [ExportsController::class, 'show'])->name('api.exports.show');

    Route::delete('/me', [AuthController::class, 'deleteAccount'])->name('api.me.delete');

    Route::get('/admin/jobs', [AdminJobsController::class, 'index'])->name('api.admin.jobs');
});

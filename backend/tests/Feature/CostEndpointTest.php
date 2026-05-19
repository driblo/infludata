<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Platforms\X\XBudgetGuard;
use Illuminate\Support\Facades\Cache;

it('exposes the X budget at /api/cost', function (): void {
    Cache::flush();
    $g = app(XBudgetGuard::class);
    $g->record(0.42);

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')->getJson('/api/cost')
        ->assertOk()
        ->assertJsonPath('data.x.spent_today_usd', 0.42)
        ->assertJsonStructure(['data' => ['x' => ['spent_today_usd', 'remaining_today_usd', 'kill_switch']]]);
});

it('requires auth for /api/cost', function (): void {
    $this->getJson('/api/cost')->assertStatus(401);
});

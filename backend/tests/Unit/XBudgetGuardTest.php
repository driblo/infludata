<?php

declare(strict_types=1);

use App\Services\Platforms\X\XBudgetGuard;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('starts at zero spend', function (): void {
    $g = new XBudgetGuard(maxDailyUsd: 5.0);
    expect($g->spentToday())->toBe(0.0)
        ->and($g->remaining())->toBe(5.0)
        ->and($g->canSpend(4.0))->toBeTrue();
});

it('records spend and refuses overages', function (): void {
    $g = new XBudgetGuard(maxDailyUsd: 1.0);
    $g->record(0.6);
    expect($g->spentToday())->toBe(0.6)
        ->and($g->remaining())->toBe(0.4)
        ->and($g->canSpend(0.3))->toBeTrue()
        ->and($g->canSpend(0.5))->toBeFalse();
});

it('honors the kill switch when the budget is zero', function (): void {
    $g = new XBudgetGuard(maxDailyUsd: 0.0);
    expect($g->isKillSwitchActive())->toBeTrue()
        ->and($g->canSpend(0.001))->toBeFalse();
});

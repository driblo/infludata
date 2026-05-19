<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Phyllo\PhylloClient;
use App\Services\Phyllo\WebhookVerifier;
use App\Services\Platforms\Facebook\FacebookGraphClient;
use App\Services\Platforms\X\XBudgetGuard;
use App\Services\Platforms\X\XClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PhylloClient::class, fn (): PhylloClient => PhylloClient::fromConfig());
        $this->app->singleton(WebhookVerifier::class, fn (): WebhookVerifier => WebhookVerifier::fromConfig());
        $this->app->singleton(XBudgetGuard::class, fn (): XBudgetGuard => XBudgetGuard::fromConfig());
        $this->app->singleton(XClient::class, fn (): XClient => XClient::fromConfig());
        $this->app->singleton(FacebookGraphClient::class, fn (): FacebookGraphClient => FacebookGraphClient::fromConfig());
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $userId = $request->user()?->getAuthIdentifier();

            return $userId !== null
                ? Limit::perMinute(60)->by((string) $userId)
                : Limit::perMinute(20)->by((string) $request->ip());
        });

        RateLimiter::for('write', function (Request $request): Limit {
            $userId = $request->user()?->getAuthIdentifier();

            return Limit::perMinute(5)->by((string) ($userId ?? $request->ip()));
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Platforms\X;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal X (Twitter) API v2 client used for poll-only public-creator
 * tracking. All calls are gated by XBudgetGuard — if the daily budget is
 * exhausted, requests return null and the caller falls back to cached data.
 *
 * Per-resource costs (May 2026 pay-per-use defaults):
 *  - Owned reads (posts, followers, lists): $0.001 per resource
 *  - Standard reads (lookup by username/id): $0.005 per resource
 */
class XClient
{
    private const COST_USER_LOOKUP = 0.005;

    private const COST_USER_TIMELINE = 0.005;

    public function __construct(
        private readonly string $bearerToken,
        private readonly XBudgetGuard $budget,
        private readonly string $baseUrl = 'https://api.x.com/2',
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            bearerToken: (string) config('services.x.bearer_token', ''),
            budget: XBudgetGuard::fromConfig(),
        );
    }

    /**
     * @return array<string, mixed>|null null when budget is exhausted
     */
    public function lookupUser(string $username): ?array
    {
        if (! $this->beforeCall(self::COST_USER_LOOKUP)) {
            return null;
        }

        $res = $this->request()->get($this->baseUrl.'/users/by/username/'.urlencode($username), [
            'user.fields' => 'public_metrics,description,verified,profile_image_url,location',
        ]);
        $this->budget->record(self::COST_USER_LOOKUP);
        $this->ensureOk($res);

        return (array) ($res->json('data') ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function userTimeline(string $userId, int $maxResults = 10): ?array
    {
        $cost = self::COST_USER_TIMELINE * $maxResults;
        if (! $this->beforeCall($cost)) {
            return null;
        }

        $res = $this->request()->get($this->baseUrl.'/users/'.$userId.'/tweets', [
            'max_results' => $maxResults,
            'tweet.fields' => 'public_metrics,created_at,entities',
        ]);
        $this->budget->record($cost);
        $this->ensureOk($res);

        return (array) $res->json();
    }

    private function beforeCall(float $cost): bool
    {
        if ($this->bearerToken === '') {
            throw new RuntimeException('X bearer token is not configured');
        }
        if ($this->budget->isKillSwitchActive()) {
            return false;
        }

        return $this->budget->canSpend($cost);
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->bearerToken)
            ->acceptJson()
            ->timeout(15);
    }

    private function ensureOk(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            sprintf('X request failed: %d %s', $response->status(), substr($response->body(), 0, 300)),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Platforms\Facebook;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Facebook Pages Graph API client. Always filters requested metrics
 * through FacebookMetricsRegistry so we never ask for metrics that are
 * deprecated as of June 15, 2026.
 */
class FacebookGraphClient
{
    public function __construct(
        private readonly string $graphVersion,
        private readonly string $appSecret,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            graphVersion: (string) config('services.meta.graph_version', 'v21.0'),
            appSecret: (string) config('services.meta.app_secret', ''),
        );
    }

    /**
     * Batch-fetch page insights for the active metrics list.
     *
     * @param  list<string>|null  $metrics  null = all active
     * @return array<string, mixed>
     */
    public function pageInsights(string $pageId, string $pageAccessToken, ?array $metrics = null): array
    {
        $requested = FacebookMetricsRegistry::filter($metrics ?? FacebookMetricsRegistry::active());
        if ($requested === []) {
            return [];
        }

        $res = Http::acceptJson()->timeout(15)->get(
            $this->base()."/{$pageId}/insights",
            [
                'metric' => implode(',', $requested),
                'period' => 'day',
                'access_token' => $pageAccessToken,
                'appsecret_proof' => $this->appsecretProof($pageAccessToken),
            ],
        );
        $this->ensureOk($res);

        return (array) ($res->json('data') ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function pageProfile(string $pageId, string $pageAccessToken): array
    {
        $res = Http::acceptJson()->timeout(15)->get(
            $this->base()."/{$pageId}",
            [
                'fields' => 'id,name,username,fan_count,followers_count,verification_status,picture',
                'access_token' => $pageAccessToken,
                'appsecret_proof' => $this->appsecretProof($pageAccessToken),
            ],
        );
        $this->ensureOk($res);

        return (array) $res->json();
    }

    private function base(): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion;
    }

    /**
     * Meta requires HMAC-SHA256(access_token, app_secret) as a proof
     * alongside the access token when the app is configured to require it.
     */
    private function appsecretProof(string $accessToken): string
    {
        if ($this->appSecret === '') {
            return '';
        }

        return hash_hmac('sha256', $accessToken, $this->appSecret);
    }

    private function ensureOk(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            sprintf('Graph request failed: %d %s', $response->status(), substr($response->body(), 0, 300)),
        );
    }
}

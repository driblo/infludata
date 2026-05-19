<?php

declare(strict_types=1);

namespace App\Services\Phyllo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP wrapper around the Phyllo unified social API.
 *
 * Endpoints used in M1:
 *  - POST /v1/users                 create or upsert a Phyllo user (one per app user)
 *  - POST /v1/sdk-tokens            mint a short-lived SDK token for the Connect modal
 *  - GET  /v1/profiles?account_id=  fetch the connected profile snapshot
 *  - GET  /v1/contents              fetch content items for an account
 *  - GET  /v1/audience              fetch demographics for an account
 *
 * The full surface is broader; we only call what we need.
 */
class PhylloClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {}

    public static function fromConfig(): self
    {
        /** @var array{base_url:string, client_id:?string, client_secret:?string} $cfg */
        $cfg = config('services.phyllo');

        return new self(
            baseUrl: rtrim((string) $cfg['base_url'], '/'),
            clientId: (string) ($cfg['client_id'] ?? ''),
            clientSecret: (string) ($cfg['client_secret'] ?? ''),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createUser(string $externalId, string $name, array $payload = []): array
    {
        return $this->json('POST', '/v1/users', array_merge([
            'name' => $name,
            'external_id' => $externalId,
        ], $payload));
    }

    /**
     * Mint a short-lived SDK token for the Connect modal.
     *
     * @param  list<string>  $products
     * @return array{sdk_token:string, expires_at:string}
     */
    public function createSdkToken(string $phylloUserId, array $products = ['IDENTITY', 'ENGAGEMENT', 'INCOME']): array
    {
        /** @var array{sdk_token:string, expires_at:string} $response */
        $response = $this->json('POST', '/v1/sdk-tokens', [
            'user_id' => $phylloUserId,
            'products' => $products,
        ]);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccount(string $accountId): array
    {
        return $this->json('GET', "/v1/accounts/{$accountId}");
    }

    /**
     * @return array<string, mixed>
     */
    public function getProfile(string $accountId): array
    {
        return $this->json('GET', '/v1/profiles', null, ['account_id' => $accountId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function listContents(string $accountId, ?string $cursor = null, int $limit = 100): array
    {
        return $this->json('GET', '/v1/contents', null, array_filter([
            'account_id' => $accountId,
            'limit' => $limit,
            'offset' => $cursor,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function getAudience(string $accountId): array
    {
        return $this->json('GET', '/v1/audience', null, ['account_id' => $accountId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getIdentityByHandle(string $network, string $handle): array
    {
        return $this->json('GET', '/v1/social/creators/profiles/search', null, [
            'work_platform_id' => $this->workPlatformId($network),
            'username' => $handle,
        ]);
    }

    private function workPlatformId(string $network): string
    {
        // Phyllo work_platform_id GUIDs (sandbox). Override via config in prod.
        /** @var array<string, string> $map */
        $map = (array) config('services.phyllo.work_platform_ids', [
            'youtube' => '14d9ddf5-51c6-415e-bde6-f8ed36ad7054',
            'instagram' => '9bb8913b-ddd9-430b-a66a-d74d846e6c66',
            'tiktok' => 'de55aeec-0dc8-4119-bf90-16b3d1f0c987',
            'x' => '7645460a-96e0-4192-a3ce-a1fc30641f72',
            'facebook' => 'ad2fec62-2987-40a0-89fb-23485972598c',
        ]);

        return $map[$network] ?? throw new RuntimeException("Unknown Phyllo network: {$network}");
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function json(string $method, string $path, ?array $body = null, array $query = []): array
    {
        $response = $this->request()
            ->when($body !== null, fn (PendingRequest $r) => $r->withBody((string) json_encode($body), 'application/json'))
            ->send($method, $this->baseUrl.$path, ['query' => $query]);

        $this->ensureOk($response);

        return (array) $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 200, function (\Throwable $e): bool {
                // Retry only on transient errors.
                return $e instanceof ConnectionException
                    || (method_exists($e, 'response') && (int) optional($e->response)->status() >= 500);
            }, throw: false);
    }

    private function ensureOk(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            sprintf('Phyllo request failed: %d %s', $response->status(), substr($response->body(), 0, 500)),
        );
    }
}

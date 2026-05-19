<?php

declare(strict_types=1);

use App\Jobs\BackfillOwnAccountJob;
use App\Models\OauthAccount;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('services.phyllo.webhook_secret', 'test-secret');
});

it('rejects requests with an invalid signature', function (): void {
    $this->postJson('/api/webhooks/phyllo', ['event' => 'ACCOUNTS.CONNECTED'], [
        'Phyllo-Signature' => 'sha256=deadbeef',
    ])->assertStatus(401);

    expect(WebhookEvent::count())->toBe(0);
});

it('accepts a valid signature and dispatches a backfill job', function (): void {
    Bus::fake();
    $user = User::factory()->create();

    $payload = [
        'event' => 'ACCOUNTS.CONNECTED',
        'data' => [
            'user' => ['id' => 'phyllo-u-1', 'external_id' => "infludata-user-{$user->id}"],
            'account' => ['id' => 'phyllo-acc-1', 'username' => 'alice-yt'],
            'work_platform' => ['name' => 'YouTube'],
        ],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'test-secret');

    $this->call(
        method: 'POST',
        uri: '/api/webhooks/phyllo',
        server: [
            'HTTP_Phyllo-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $body,
    )->assertOk();

    expect(OauthAccount::where('user_id', $user->id)->first())->not->toBeNull()
        ->and(OauthAccount::where('user_id', $user->id)->first()->network)->toBe('youtube');

    Bus::assertDispatched(BackfillOwnAccountJob::class);
});

it('is idempotent on duplicate ACCOUNTS.CONNECTED events', function (): void {
    Bus::fake();
    $user = User::factory()->create();

    $payload = [
        'event' => 'ACCOUNTS.CONNECTED',
        'data' => [
            'user' => ['id' => 'phyllo-u-1', 'external_id' => "infludata-user-{$user->id}"],
            'account' => ['id' => 'phyllo-acc-1', 'username' => 'alice-yt'],
            'work_platform' => ['name' => 'YouTube'],
        ],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'test-secret');

    foreach ([1, 2] as $i) {
        $this->call(
            method: 'POST',
            uri: '/api/webhooks/phyllo',
            server: [
                'HTTP_Phyllo-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        )->assertOk();
    }

    expect(OauthAccount::where('phyllo_account_id', 'phyllo-acc-1')->count())->toBe(1);
});

it('revokes the oauth account on ACCOUNTS.DISCONNECTED', function (): void {
    $user = User::factory()->create();
    OauthAccount::create([
        'user_id' => $user->id,
        'network' => 'youtube',
        'phyllo_account_id' => 'phyllo-acc-1',
        'status' => 'connected',
        'connected_at' => now(),
    ]);

    $payload = [
        'event' => 'ACCOUNTS.DISCONNECTED',
        'data' => ['account' => ['id' => 'phyllo-acc-1']],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'test-secret');

    $this->call(
        method: 'POST',
        uri: '/api/webhooks/phyllo',
        server: [
            'HTTP_Phyllo-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $body,
    )->assertOk();

    expect(OauthAccount::where('phyllo_account_id', 'phyllo-acc-1')->first()->status)
        ->toBe('revoked');
});

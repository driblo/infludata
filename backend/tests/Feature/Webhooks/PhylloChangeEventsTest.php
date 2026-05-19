<?php

declare(strict_types=1);

use App\Jobs\BackfillOwnAccountJob;
use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function (): void {
    Config::set('services.phyllo.webhook_secret', 'test-secret');
});

function postEvent(TestCase $t, string $event, array $data): TestResponse
{
    $payload = ['event' => $event, 'data' => $data];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $sig = hash_hmac('sha256', $body, 'test-secret');

    return $t->call(
        method: 'POST',
        uri: '/api/webhooks/phyllo',
        server: [
            'HTTP_Phyllo-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $body,
    );
}

it('dispatches a backfill on PROFILES.UPDATED for a known account', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    OauthAccount::create([
        'user_id' => $user->id, 'network' => 'youtube',
        'phyllo_account_id' => 'phyllo-acc-7', 'status' => 'connected',
        'connected_at' => now(),
    ]);

    postEvent($this, 'PROFILES.UPDATED', ['account' => ['id' => 'phyllo-acc-7']])
        ->assertOk();

    Bus::assertDispatched(BackfillOwnAccountJob::class, fn ($job) => $job->phylloAccountId === 'phyllo-acc-7');
});

it('dispatches a backfill on CONTENTS.ADDED', function (): void {
    Bus::fake();
    $user = User::factory()->create();
    OauthAccount::create([
        'user_id' => $user->id, 'network' => 'instagram',
        'phyllo_account_id' => 'phyllo-acc-8', 'status' => 'connected',
        'connected_at' => now(),
    ]);

    postEvent($this, 'CONTENTS.ADDED', ['account' => ['id' => 'phyllo-acc-8']])
        ->assertOk();

    Bus::assertDispatched(BackfillOwnAccountJob::class);
});

it('ignores PROFILES.UPDATED for an unknown account without dispatching', function (): void {
    Bus::fake();

    postEvent($this, 'PROFILES.UPDATED', ['account' => ['id' => 'never-seen']])
        ->assertOk();

    Bus::assertNothingDispatched();
});

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\BackfillOwnAccountJob;
use App\Models\OauthAccount;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Phyllo\WebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhylloWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookVerifier $verifier): JsonResponse
    {
        $raw = $request->getContent();
        $signatureOk = $verifier->verify($raw, $request->header('Phyllo-Signature'));

        if (! $signatureOk) {
            Log::warning('phyllo.webhook.bad_signature', ['ip' => $request->ip()]);

            return response()->json(['detail' => 'invalid signature'], 401);
        }

        /** @var array<string, mixed> $payload */
        $payload = (array) $request->json()->all();
        $eventType = (string) ($payload['event'] ?? 'unknown');

        $event = WebhookEvent::create([
            'provider' => 'phyllo',
            'event_type' => $eventType,
            'payload' => $payload,
            'signature_ok' => true,
            'received_at' => now(),
            'status' => 'pending',
        ]);

        try {
            $this->dispatchFor($eventType, $payload);
            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $event->update([
                'status' => 'failed',
                'attempts' => ($event->attempts ?? 0) + 1,
                'error' => substr($e->getMessage(), 0, 1000),
            ]);
            Log::error('phyllo.webhook.handler_failed', [
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatchFor(string $eventType, array $payload): void
    {
        /** @var array<string, mixed> $data */
        $data = (array) ($payload['data'] ?? []);

        match ($eventType) {
            'ACCOUNTS.CONNECTED' => $this->onAccountConnected($data),
            'ACCOUNTS.DISCONNECTED' => $this->onAccountDisconnected($data),
            // PROFILES.UPDATED / CONTENTS.ADDED / AUDIENCE.UPDATED handled in later milestones.
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function onAccountConnected(array $data): void
    {
        $externalUserId = (string) ($data['user']['external_id'] ?? '');
        if (! str_starts_with($externalUserId, 'infludata-user-')) {
            Log::info('phyllo.webhook.ignored', ['reason' => 'no external_id', 'data' => $data]);

            return;
        }

        $userId = (int) substr($externalUserId, strlen('infludata-user-'));
        $user = User::find($userId);
        if ($user === null) {
            Log::warning('phyllo.webhook.user_not_found', ['user_id' => $userId]);

            return;
        }

        $accountId = (string) ($data['account']['id'] ?? '');
        $network = strtolower((string) ($data['work_platform']['name'] ?? ''));
        if ($accountId === '' || $network === '') {
            return;
        }

        DB::transaction(function () use ($user, $data, $accountId, $network): void {
            OauthAccount::updateOrCreate(
                ['user_id' => $user->getKey(), 'phyllo_account_id' => $accountId],
                [
                    'network' => $network,
                    'phyllo_user_id' => (string) ($data['user']['id'] ?? ''),
                    'external_handle' => (string) ($data['account']['username'] ?? null),
                    'status' => 'connected',
                    'connected_at' => now(),
                ],
            );
        });

        BackfillOwnAccountJob::dispatch($accountId)->onQueue('high');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function onAccountDisconnected(array $data): void
    {
        $accountId = (string) ($data['account']['id'] ?? '');
        if ($accountId === '') {
            return;
        }

        OauthAccount::where('phyllo_account_id', $accountId)
            ->update(['status' => 'revoked']);
    }
}

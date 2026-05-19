<?php

declare(strict_types=1);

namespace App\Services\Phyllo;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Lazily map every infludata user to a single Phyllo user, and mint
 * short-lived SDK tokens for the Connect modal.
 *
 * The Phyllo user_id is cached for the lifetime of the user.
 */
class PhylloSdkTokenService
{
    public function __construct(private readonly PhylloClient $client) {}

    /**
     * @return array{sdk_token:string, expires_at:string, phyllo_user_id:string}
     */
    public function mintTokenFor(User $user): array
    {
        $phylloUserId = $this->phylloUserIdFor($user);
        $token = $this->client->createSdkToken($phylloUserId);

        return [
            'sdk_token' => $token['sdk_token'],
            'expires_at' => $token['expires_at'],
            'phyllo_user_id' => $phylloUserId,
        ];
    }

    public function phylloUserIdFor(User $user): string
    {
        return Cache::rememberForever("phyllo:user:{$user->getKey()}", function () use ($user): string {
            $created = $this->client->createUser(
                externalId: 'infludata-user-'.$user->getKey(),
                name: (string) $user->getAttribute('name'),
            );

            return (string) ($created['id'] ?? throw new \RuntimeException('Phyllo user creation returned no id'));
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Platforms\TikTok;

use App\Services\Phyllo\PhylloClient;

/**
 * TikTok client.
 *
 * Notes:
 *  - The TikTok Research API is academic-only as of 2026, so we cannot
 *    use it for product analytics. Public-creator data comes from Phyllo.
 *  - The Display API (for users who connect their OWN TikTok) is also
 *    reached through Phyllo's unified flow in this codebase.
 *
 * This class is a thin re-export of Phyllo specifically for TikTok-flavored
 * call sites. Keeping it separate makes it cheap to swap providers later
 * without touching ingestion jobs.
 */
class TikTokClient
{
    public function __construct(private readonly PhylloClient $phyllo) {}

    /**
     * @return array<string, mixed>
     */
    public function lookupByHandle(string $handle): array
    {
        return $this->phyllo->getIdentityByHandle('tiktok', $handle);
    }

    /**
     * @return array<string, mixed>
     */
    public function contents(string $accountId): array
    {
        return $this->phyllo->listContents($accountId);
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

final class Network
{
    public const YOUTUBE = 'youtube';

    public const INSTAGRAM = 'instagram';

    public const TIKTOK = 'tiktok';

    public const X = 'x';

    public const FACEBOOK = 'facebook';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::YOUTUBE, self::INSTAGRAM, self::TIKTOK, self::X, self::FACEBOOK];
    }

    public static function isEnabled(string $network): bool
    {
        /** @var array<string, bool> $flags */
        $flags = config('services.networks', []);

        return (bool) ($flags[$network] ?? false);
    }
}

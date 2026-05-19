<?php

declare(strict_types=1);

namespace App\Services\Phyllo;

/**
 * Verify Phyllo webhook signatures.
 *
 * Phyllo signs requests with HMAC-SHA256(secret, raw_body) and sends the hex
 * digest in the `Phyllo-Signature` header.
 */
class WebhookVerifier
{
    public function __construct(private readonly string $secret) {}

    public static function fromConfig(): self
    {
        return new self((string) config('services.phyllo.webhook_secret', ''));
    }

    public function verify(string $rawBody, ?string $signatureHeader): bool
    {
        if ($this->secret === '' || $signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->secret);

        return hash_equals($expected, $this->normalize($signatureHeader));
    }

    private function normalize(string $value): string
    {
        // Allow either "abc..." or "sha256=abc..." style headers.
        return str_starts_with($value, 'sha256=') ? substr($value, 7) : $value;
    }
}

<?php

declare(strict_types=1);

use App\Services\Phyllo\WebhookVerifier;

it('returns false when the secret is empty', function (): void {
    $verifier = new WebhookVerifier('');

    expect($verifier->verify('hello', 'sig'))->toBeFalse();
});

it('returns false when the signature is missing', function (): void {
    $verifier = new WebhookVerifier('s');

    expect($verifier->verify('hello', null))->toBeFalse()
        ->and($verifier->verify('hello', ''))->toBeFalse();
});

it('verifies a correct hex digest', function (): void {
    $verifier = new WebhookVerifier('shh');
    $body = '{"event":"X"}';
    $sig = hash_hmac('sha256', $body, 'shh');

    expect($verifier->verify($body, $sig))->toBeTrue()
        ->and($verifier->verify($body, 'sha256='.$sig))->toBeTrue();
});

it('rejects a tampered body', function (): void {
    $verifier = new WebhookVerifier('shh');
    $sig = hash_hmac('sha256', 'original', 'shh');

    expect($verifier->verify('tampered', $sig))->toBeFalse();
});

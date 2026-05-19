<?php

declare(strict_types=1);

use App\Models\CreatorProfile;
use App\Services\Ingestion\IdentityResolutionService;
use App\Services\Phyllo\PhylloClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('upserts a profile from a Phyllo identity lookup', function (): void {
    $phyllo = Mockery::mock(PhylloClient::class);
    $phyllo->shouldReceive('getIdentityByHandle')
        ->with('youtube', 'mrbeast')
        ->andReturn([
            'data' => [[
                'platform_user_id' => 'UCx6...',
                'username' => 'mrbeast',
                'full_name' => 'MrBeast',
                'reputation' => ['follower_count' => 250000000, 'following_count' => 100],
                'is_verified' => true,
            ]],
        ]);

    $svc = new IdentityResolutionService($phyllo);
    $profile = $svc->resolve('youtube', '@mrbeast');

    expect($profile)->toBeInstanceOf(CreatorProfile::class)
        ->and($profile->platform_user_id)->toBe('UCx6...')
        ->and($profile->handle)->toBe('mrbeast')
        ->and($profile->follower_count)->toBe(250000000)
        ->and($profile->is_verified)->toBeTrue();
});

it('throws when Phyllo returns no platform_user_id', function (): void {
    $phyllo = Mockery::mock(PhylloClient::class);
    $phyllo->shouldReceive('getIdentityByHandle')->andReturn(['data' => []]);

    $svc = new IdentityResolutionService($phyllo);
    expect(fn () => $svc->resolve('youtube', 'ghost'))
        ->toThrow(RuntimeException::class);
});

it('throws on empty handle', function (): void {
    $phyllo = Mockery::mock(PhylloClient::class);
    $svc = new IdentityResolutionService($phyllo);
    expect(fn () => $svc->resolve('youtube', '   '))->toThrow(RuntimeException::class);
});

<?php

declare(strict_types=1);

it('responds to /api/health with app=ok', function (): void {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonPath('checks.app', 'ok')
        ->assertJsonStructure(['status', 'checks' => ['app', 'db', 'redis'], 'version']);
});

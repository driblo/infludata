<?php

declare(strict_types=1);

use App\Jobs\BuildExportJob;
use App\Models\ExportRequest;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

it('queues an export and dispatches the job', function (): void {
    Bus::fake();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/exports', ['kind' => 'json'])
        ->assertStatus(202)
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('kind', 'json');

    Bus::assertDispatched(BuildExportJob::class);
});

it('builds a JSON export file on disk', function (): void {
    Storage::fake('local');
    $user = User::factory()->create();
    $export = ExportRequest::create([
        'user_id' => $user->id, 'kind' => 'json', 'status' => 'pending',
        'requested_at' => now(),
    ]);

    (new BuildExportJob($export->id))->handle();

    $export->refresh();
    expect($export->status)->toBe('completed')
        ->and($export->file_url)->not->toBeNull();
});

it('refuses to expose another user export', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $export = ExportRequest::create([
        'user_id' => $bob->id, 'kind' => 'json', 'status' => 'completed',
        'requested_at' => now(),
    ]);

    $this->actingAs($alice, 'sanctum')
        ->getJson("/api/exports/{$export->id}")
        ->assertStatus(403);
});

it('hard-deletes a user account via DELETE /api/me', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')->deleteJson('/api/me')->assertNoContent();
    expect(User::find($user->id))->toBeNull();
});

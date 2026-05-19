<?php

declare(strict_types=1);

use App\Models\User;

it('blocks non-admin from /admin/jobs with 403', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user, 'sanctum')->getJson('/api/admin/jobs')->assertStatus(403);
});

it('lets admins see the jobs list', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin, 'sanctum')->getJson('/api/admin/jobs')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

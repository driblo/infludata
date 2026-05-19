<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('network', 32);
            $table->string('phyllo_account_id', 64)->nullable();
            $table->string('phyllo_user_id', 64)->nullable();
            $table->string('external_handle')->nullable();
            $table->jsonb('scopes')->nullable();
            $table->string('status', 16)->default('connected');
            $table->timestampTz('connected_at')->nullable();
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'phyllo_account_id']);
            $table->index(['user_id', 'network']);
        });

        Schema::create('creator_profiles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('network', 32);
            $table->string('platform_user_id', 128);
            $table->string('handle');
            $table->string('display_name')->nullable();
            $table->string('avatar_url', 1024)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedBigInteger('follower_count')->default(0);
            $table->unsignedBigInteger('following_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->string('country', 8)->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestampTz('fetched_at')->nullable();
            $table->timestampsTz();
            $table->unique(['network', 'platform_user_id']);
            $table->index(['network', 'handle']);
        });

        Schema::create('tracked_creators', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_profile_id')->constrained()->cascadeOnDelete();
            $table->string('network', 32);
            $table->string('handle');
            $table->string('label')->nullable();
            $table->unsignedInteger('refresh_cadence_minutes')->default(1440);
            $table->timestampTz('added_at')->useCurrent();
            $table->timestampsTz();
            $table->unique(['user_id', 'creator_profile_id']);
            $table->index(['user_id', 'network']);
        });

        Schema::create('content_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('creator_profile_id')->constrained()->cascadeOnDelete();
            $table->string('network', 32);
            $table->string('external_id', 128);
            $table->string('kind', 32);
            $table->text('title')->nullable();
            $table->text('caption')->nullable();
            $table->string('url', 1024)->nullable();
            $table->string('thumbnail_url', 1024)->nullable();
            $table->unsignedInteger('duration_s')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampsTz();
            $table->unique(['network', 'external_id']);
            $table->index(['creator_profile_id', 'published_at']);
        });

        Schema::create('audience_demographics', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('oauth_account_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('captured_at');
            $table->string('dimension', 32);
            $table->string('bucket', 64);
            $table->decimal('value_pct', 6, 3);
            $table->jsonb('raw')->nullable();
            $table->timestampsTz();
            $table->index(['oauth_account_id', 'captured_at']);
            $table->index(['oauth_account_id', 'dimension']);
        });

        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('provider', 32);
            $table->string('event_type', 64);
            $table->jsonb('payload');
            $table->boolean('signature_ok')->default(false);
            $table->timestampTz('received_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->index(['provider', 'event_type']);
            $table->index('status');
        });

        Schema::create('api_jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('job_class');
            $table->jsonb('payload')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->string('status', 16)->default('queued');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('oauth_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tracked_creator_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
        });

        Schema::create('alerts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 16);
            $table->unsignedBigInteger('target_id');
            $table->string('kind', 32);
            $table->jsonb('threshold');
            $table->string('channel', 16)->default('email');
            $table->boolean('enabled')->default(true);
            $table->timestampTz('last_fired_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'enabled']);
        });

        Schema::create('export_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('status', 16)->default('pending');
            $table->string('file_url', 1024)->nullable();
            $table->timestampTz('requested_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_requests');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('api_jobs');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('audience_demographics');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('tracked_creators');
        Schema::dropIfExists('creator_profiles');
        Schema::dropIfExists('oauth_accounts');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPostgres = DB::connection()->getDriverName() === 'pgsql';

        if ($isPostgres) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS timescaledb');
        }

        Schema::create('metric_snapshots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('creator_profile_id')->constrained()->cascadeOnDelete();
            $table->string('network', 32);
            $table->timestampTz('captured_at');
            $table->unsignedBigInteger('followers')->default(0);
            $table->unsignedBigInteger('following')->default(0);
            $table->unsignedBigInteger('posts_count')->default(0);
            $table->unsignedBigInteger('total_likes')->default(0);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->decimal('engagement_rate', 6, 4)->nullable();
            $table->string('source', 16)->default('phyllo');
            $table->jsonb('raw')->nullable();
            $table->index(['creator_profile_id', 'captured_at']);
        });

        Schema::create('content_metrics', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('captured_at');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('watch_time_s')->default(0);
            $table->index(['content_item_id', 'captured_at']);
        });

        if ($isPostgres) {
            // Convert to TimescaleDB hypertables. Skipped on sqlite/mysql so tests can still run.
            DB::statement("SELECT create_hypertable('metric_snapshots', 'captured_at', chunk_time_interval => INTERVAL '7 days', if_not_exists => TRUE)");
            DB::statement("SELECT create_hypertable('content_metrics', 'captured_at', chunk_time_interval => INTERVAL '14 days', if_not_exists => TRUE)");

            // Compress raw chunks older than 7 days.
            DB::statement("ALTER TABLE metric_snapshots SET (timescaledb.compress, timescaledb.compress_segmentby = 'creator_profile_id')");
            DB::statement("ALTER TABLE content_metrics SET (timescaledb.compress, timescaledb.compress_segmentby = 'content_item_id')");
            DB::statement("SELECT add_compression_policy('metric_snapshots', INTERVAL '7 days', if_not_exists => TRUE)");
            DB::statement("SELECT add_compression_policy('content_metrics', INTERVAL '14 days', if_not_exists => TRUE)");

            // Retention: keep raw 18 months.
            DB::statement("SELECT add_retention_policy('metric_snapshots', INTERVAL '540 days', if_not_exists => TRUE)");
            DB::statement("SELECT add_retention_policy('content_metrics', INTERVAL '540 days', if_not_exists => TRUE)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_metrics');
        Schema::dropIfExists('metric_snapshots');
    }
};

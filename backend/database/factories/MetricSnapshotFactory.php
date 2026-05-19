<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreatorProfile;
use App\Models\MetricSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetricSnapshot>
 */
class MetricSnapshotFactory extends Factory
{
    protected $model = MetricSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'network' => 'youtube',
            'captured_at' => now(),
            'followers' => $this->faker->numberBetween(1000, 5_000_000),
            'following' => $this->faker->numberBetween(0, 5000),
            'posts_count' => $this->faker->numberBetween(0, 5000),
            'total_likes' => $this->faker->numberBetween(0, 10_000_000),
            'total_views' => $this->faker->numberBetween(0, 100_000_000),
            'engagement_rate' => $this->faker->randomFloat(4, 0, 0.2),
            'source' => 'phyllo',
            'raw' => [],
        ];
    }
}

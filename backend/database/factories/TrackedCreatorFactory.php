<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreatorProfile;
use App\Models\TrackedCreator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedCreator>
 */
class TrackedCreatorFactory extends Factory
{
    protected $model = TrackedCreator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'creator_profile_id' => CreatorProfile::factory(),
            'network' => 'youtube',
            'handle' => $this->faker->userName(),
            'label' => null,
            'refresh_cadence_minutes' => 1440,
            'added_at' => now(),
        ];
    }
}

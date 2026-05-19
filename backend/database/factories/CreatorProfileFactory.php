<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreatorProfile;
use App\Support\Network;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorProfile>
 */
class CreatorProfileFactory extends Factory
{
    protected $model = CreatorProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'network' => $this->faker->randomElement(Network::all()),
            'platform_user_id' => 'pf-'.$this->faker->unique()->uuid(),
            'handle' => $this->faker->userName(),
            'display_name' => $this->faker->name(),
            'avatar_url' => $this->faker->imageUrl(),
            'bio' => $this->faker->sentence(),
            'follower_count' => $this->faker->numberBetween(100, 10_000_000),
            'following_count' => $this->faker->numberBetween(0, 5000),
            'is_verified' => $this->faker->boolean(20),
            'country' => strtoupper($this->faker->countryCode()),
            'raw_payload' => [],
            'fetched_at' => now(),
        ];
    }
}

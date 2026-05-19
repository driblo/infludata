<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_type' => 'creator',
            'target_id' => 1,
            'kind' => 'follower_milestone',
            'threshold' => ['min_followers' => 1000],
            'channel' => 'email',
            'enabled' => true,
        ];
    }
}

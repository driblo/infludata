<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OauthAccount;
use App\Models\User;
use App\Support\Network;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OauthAccount>
 */
class OauthAccountFactory extends Factory
{
    protected $model = OauthAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'network' => $this->faker->randomElement(Network::all()),
            'phyllo_account_id' => 'acc-'.$this->faker->unique()->uuid(),
            'phyllo_user_id' => 'u-'.$this->faker->uuid(),
            'external_handle' => $this->faker->userName(),
            'scopes' => [],
            'status' => 'connected',
            'connected_at' => now(),
            'last_synced_at' => null,
        ];
    }
}

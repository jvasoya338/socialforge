<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'social_platform_id' => SocialPlatform::factory(),
            'account_name' => fake()->userName(),
            'username' => fake()->userName(),
            'platform_account_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'account_type' => 'page',
            'status' => 'active',
            'connection_status' => SocialAccount::CONNECTION_CONNECTED,
            'connected_at' => now(),
            'metadata' => [],
        ];
    }
}

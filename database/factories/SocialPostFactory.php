<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialPostFactory extends Factory
{
    protected $model = SocialPost::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'social_platform_id' => SocialPlatform::factory(),
            'social_account_id' => SocialAccount::factory(),
            'created_by' => User::factory(),
            'body' => fake()->paragraph(),
            'hashtags' => ['laravel', 'socialforge'],
            'status' => SocialPost::STATUS_DRAFT,
            'approval_status' => SocialPost::APPROVAL_PENDING,
            'publish_mode' => SocialPost::PUBLISH_MODE_MANUAL,
        ];
    }
}

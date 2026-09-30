<?php

namespace Database\Factories;

use App\Models\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SocialPlatformFactory extends Factory
{
    protected $model = SocialPlatform::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 9999),
            'icon' => 'fab fa-share',
            'status' => 'active',
            'supports_text' => true,
            'supports_image' => true,
            'supports_video' => true,
            'supports_reels' => false,
            'supports_stories' => false,
            'supports_analytics' => true,
            'supports_scheduling' => true,
            'max_text_length' => 280,
            'configuration' => [],
        ];
    }
}

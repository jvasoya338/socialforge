<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\ContentSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentSourceFactory extends Factory
{
    protected $model = ContentSource::class;

    public function definition(): array
    {
        $rawKey = 'sf_live_'.Str::random(40);

        return [
            'brand_id' => Brand::factory(),
            'name' => fake()->words(2, true).' Ingestion',
            'type' => ContentSource::TYPE_API,
            'api_key_hash' => hash('sha256', $rawKey),
            'api_key_prefix' => substr($rawKey, 0, 12),
            'rate_limit_per_minute' => 60,
            'is_active' => true,
        ];
    }
}

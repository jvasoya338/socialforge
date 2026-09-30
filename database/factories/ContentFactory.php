<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'external_id' => (string) fake()->unique()->numberBetween(10000, 99999),
            'type' => Content::TYPE_ARTICLE,
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'summary' => fake()->sentence(),
            'status' => Content::STATUS_READY,
        ];
    }
}

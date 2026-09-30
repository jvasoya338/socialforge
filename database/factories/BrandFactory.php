<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'website_url' => fake()->url(),
            'description' => fake()->paragraph(),
            'timezone' => 'UTC',
            'default_language' => 'en',
            'status' => Brand::STATUS_ACTIVE,
        ];
    }
}

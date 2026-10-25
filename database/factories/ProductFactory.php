<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['name' => fake()->unique()->words(3, true), 'description' => fake()->paragraph(), 'category_id' => Category::factory(), 'price' => fake()->randomFloat(2, 1, 900), 'rating' => fake()->randomFloat(1, 0, 5)];
    }
}

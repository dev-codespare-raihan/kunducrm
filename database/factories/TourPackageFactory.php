<?php

namespace Database\Factories;

use App\Models\TourPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourPackage>
 */
class TourPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'duration' => '8 hours',
            'price_label' => '₹1,800',
            'car' => 'Swift Dzire',
            'image_alt' => fake()->sentence(),
            'description' => fake()->sentence(),
            'stops' => ['Pickup', 'Drop'],
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}

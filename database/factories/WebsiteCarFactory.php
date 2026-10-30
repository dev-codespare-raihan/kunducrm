<?php

namespace Database\Factories;

use App\Models\WebsiteCar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebsiteCar>
 */
class WebsiteCarFactory extends Factory
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
            'type' => 'Sedan · 4 seater',
            'seats' => '4+1',
            'fuel' => 'Petrol / CNG',
            'local_label' => '₹1,800',
            'best_for' => 'Daily city and airport',
            'image_alt' => fake()->sentence(),
            'image_width' => 1200,
            'image_height' => 675,
            'blurb' => fake()->sentence(),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}

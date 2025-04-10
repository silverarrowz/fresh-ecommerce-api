<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement([
                'Kultlab Pineapple Isotonic',
                'Kultlab Whey Protein',
                'Батончик Chikabar в белом шоколаде',
                'Fresh Iron'
            ]),
            'price' => $this->faker->randomFloat(0, 75, 1400),
            'price_old' => fake()->randomFloat(0, 100, 2800),
            'description' => $this->faker->paragraph(),
            'stock' => $this->faker->randomNumber(3),

            'category' => $this->faker->randomElement([
                'Протеиновые батончики и печенье',
                'Протеины',
                'Витамины и минералы',
                'Изотоники'
            ])
        ];
    }
}

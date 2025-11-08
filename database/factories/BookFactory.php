<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'isbn' => fake()->unique()->isbn13(),
            'publisher' => fake()->company(),
            'publication_year' => fake()->year(),
            'store_location' => fake()->randomElement(['Denpasar', 'Badung', 'Tabanan', 'Buleleng', 'Jembrana', 'Klungkung', 'Gianyar', 'Bangli', 'Karangasem']),
            'status' => fake()->randomElement(['available', 'rented', 'reserved'])
        ];
    }
}

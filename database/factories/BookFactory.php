<?php

namespace Database\Factories;

use App\Enums\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Resolve every related record using the same generated owner.
            'author_id' => fn (array $attributes) => Author::factory()->create(['user_id' => $attributes['user_id']])->id,
            'genre_id' => fn (array $attributes) => Genre::factory()->create(['user_id' => $attributes['user_id']])->id,
            'shelf_id' => fn (array $attributes) => Shelf::factory()->create(['user_id' => $attributes['user_id']])->id,
            'title' => fake()->sentence(4),
            'total_pages' => fake()->numberBetween(80, 800),
            'current_page' => 0,
            'status' => BookStatus::WantToRead->value,
            'notes' => '',
            'cover_url' => '',
        ];
    }
}

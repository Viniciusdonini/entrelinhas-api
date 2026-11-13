<?php

namespace Database\Seeders;

use App\Enums\BookStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Database\Seeder;

class LibraryDemoSeeder extends Seeder
{
    /**
     * Only called explicitly for the configured demonstration user.
     * Existing records are retained; running the seeder twice adds no duplicates.
     */
    public function seedFor(User $user): void
    {
        $genre = Genre::query()->firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Literatura'],
            Genre::factory()->make(['user_id' => $user->id, 'description' => 'Histórias para a biblioteca pessoal.'])->only(['description']),
        );
        $shelf = Shelf::query()->firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Estante principal'],
            Shelf::factory()->make(['user_id' => $user->id, 'description' => 'Livros separados para a apresentação.', 'location' => 'Sala de leitura'])->only(['description', 'location']),
        );
        $examples = [
            ['title' => 'A hora da estrela', 'author' => 'Clarice Lispector', 'total' => 96, 'current' => 96, 'status' => BookStatus::Finished],
            ['title' => 'O alienista', 'author' => 'Machado de Assis', 'total' => 88, 'current' => 28, 'status' => BookStatus::Reading],
            ['title' => 'O pequeno príncipe', 'author' => 'Antoine de Saint-Exupéry', 'total' => 96, 'current' => 0, 'status' => BookStatus::WantToRead],
        ];
        foreach ($examples as $example) {
            $author = Author::query()->firstOrCreate(
                ['user_id' => $user->id, 'name' => $example['author']],
                Author::factory()->make(['user_id' => $user->id, 'bio' => 'Autoria de um dos livros da demonstração.'])->only(['bio']),
            );
            if (Book::query()->where('user_id', $user->id)->where('title', $example['title'])->exists()) {
                continue;
            }
            Book::factory()->create([
                'user_id' => $user->id,
                'author_id' => $author->id,
                'genre_id' => $genre->id,
                'shelf_id' => $shelf->id,
                'title' => $example['title'],
                'total_pages' => $example['total'],
                'current_page' => $example['current'],
                'status' => $example['status']->value,
                'notes' => 'Registro da demonstração, persistido pela API Laravel.',
            ]);
        }
    }
}

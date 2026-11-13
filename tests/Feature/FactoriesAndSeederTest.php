<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Shelf;
use App\Models\User;
use Database\Seeders\LibraryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesAndSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_book_factories_keep_all_relations_under_the_same_owner(): void
    {
        $owner = User::factory()->create();
        $books = [
            Book::factory()->create(['user_id' => $owner->id]),
            Book::factory()->create(),
        ];
        foreach ($books as $book) {
            $this->assertSame($book->user_id, $book->author->user_id);
            $this->assertSame($book->user_id, $book->genre->user_id);
            $this->assertSame($book->user_id, $book->shelf->user_id);
            $this->assertSame('wantToRead', $book->status);
            $this->assertSame(0, $book->current_page);
            $this->assertGreaterThan(0, $book->total_pages);
        }
        $this->assertDatabaseCount('books', 2);
    }

    public function test_demo_seeder_is_idempotent_and_does_not_touch_another_user(): void
    {
        $owner = User::factory()->create();
        $foreign = Book::factory()->create(['notes' => 'Registro de outro usuário.']);
        $seeder = new LibraryDemoSeeder;
        $seeder->seedFor($owner);
        $counts = [];
        foreach ([Author::class, Genre::class, Shelf::class, Book::class] as $model) {
            $counts[$model] = $model::query()->where('user_id', $owner->id)->count();
        }
        $this->assertSame([3, 1, 1, 3], array_values($counts));

        $seeder->seedFor($owner);
        foreach ($counts as $model => $count) {
            $this->assertSame($count, $model::query()->where('user_id', $owner->id)->count());
        }
        foreach (Book::query()->where('user_id', $owner->id)->get() as $book) {
            $this->assertSame($owner->id, $book->author->user_id);
            $this->assertSame($owner->id, $book->genre->user_id);
            $this->assertSame($owner->id, $book->shelf->user_id);
        }
        $this->assertDatabaseHas('books', ['id' => $foreign->id, 'notes' => 'Registro de outro usuário.']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_demo_seeder_preserves_an_existing_book_with_the_same_title(): void
    {
        $owner = User::factory()->create();
        $existing = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => 'O alienista',
            'notes' => 'Minhas anotações já existentes.',
        ]);
        $authorId = $existing->author_id;
        (new LibraryDemoSeeder)->seedFor($owner);

        $this->assertSame(1, Book::query()->where('user_id', $owner->id)->where('title', 'O alienista')->count());
        $this->assertSame('Minhas anotações já existentes.', $existing->fresh()->notes);
        $this->assertSame($authorId, $existing->fresh()->author_id);
    }
}

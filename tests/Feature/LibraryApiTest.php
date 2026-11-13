<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public static function resources(): array
    {
        return [
            'authors' => ['authors', Author::class, ['name' => 'Autora de teste', 'bio' => 'Biografia.']],
            'genres' => ['genres', Genre::class, ['name' => 'Romance de teste', 'description' => 'Descrição.']],
            'shelves' => ['shelves', Shelf::class, ['name' => 'Estante de teste', 'description' => 'Descrição.', 'location' => 'Sala']],
            'books' => ['books', Book::class, self::bookPayload()],
        ];
    }

    public static function catalogs(): array
    {
        return [
            'author' => ['authors', 'author_id'],
            'genre' => ['genres', 'genre_id'],
            'shelf' => ['shelves', 'shelf_id'],
        ];
    }

    #[DataProvider('resources')]
    public function test_four_resources_support_crud_without_exposing_another_owner(string $resource, string $model, array $payload): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $model::factory()->create(['user_id' => $other->id]);
        Sanctum::actingAs($owner);

        $created = $this->postJson("/api/$resource", $payload)->assertCreated()->assertJsonStructure(['data' => ['id', 'createdAt', 'updatedAt']]);
        $id = $created->json('data.id');
        $this->assertIsString($id);
        $this->assertNotSame((string) $foreign->id, $id);

        $this->getJson("/api/$resource")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->getJson("/api/$resource/$id")->assertOk()->assertJsonPath('data.id', $id);
        $edited = array_replace($payload, [$resource === 'books' ? 'title' : 'name' => 'Cadastro editado']);
        $this->putJson("/api/$resource/$id", $edited)->assertOk()->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.'.($resource === 'books' ? 'title' : 'name'), 'Cadastro editado')
            ->assertJsonPath('data.createdAt', $created->json('data.createdAt'));

        $this->getJson("/api/$resource/{$foreign->id}")->assertNotFound();
        $this->getJson("/api/$resource/999999")->assertNotFound();
        $foreignEdit = array_replace($edited, [$resource === 'books' ? 'title' : 'name' => 'Tentativa em outro usuário']);
        $this->putJson("/api/$resource/{$foreign->id}", $foreignEdit)->assertNotFound();
        $this->deleteJson("/api/$resource/{$foreign->id}")->assertNotFound();
        $this->assertDatabaseHas($foreign->getTable(), ['id' => $foreign->id, 'user_id' => $other->id]);

        $this->deleteJson("/api/$resource/$id")->assertNoContent();
        $this->getJson("/api/$resource/$id")->assertNotFound();
        $this->getJson("/api/$resource")->assertOk()->assertJsonCount(0, 'data');
    }

    #[DataProvider('resources')]
    public function test_resource_requests_reject_owner_injection(string $resource, string $model, array $payload): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($owner);

        foreach (['user_id', 'userId'] as $field) {
            $this->postJson("/api/$resource", [...$payload, $field => $other->id])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount((new $model)->getTable(), 0);
    }

    #[DataProvider('catalogs')]
    public function test_catalog_names_are_validated_per_owner_and_current_record_is_ignored_on_update(string $resource, string $foreignKey): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $this->postJson("/api/$resource", ['name' => '   '])->assertUnprocessable()->assertJsonValidationErrors('name');

        $created = $this->postJson("/api/$resource", ['name' => '  Cadastro único  '])->assertCreated();
        $id = $created->json('data.id');
        $created->assertJsonPath('data.name', 'Cadastro único');
        $this->postJson("/api/$resource", ['name' => 'Cadastro único'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson("/api/$resource/$id", ['name' => 'Cadastro único'])->assertOk();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/$resource", ['name' => 'Cadastro único'])->assertCreated();
    }

    #[DataProvider('catalogs')]
    public function test_catalog_deletion_preserves_referential_integrity(string $resource, string $foreignKey): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);
        $id = $book->$foreignKey;

        $this->deleteJson("/api/$resource/$id")->assertConflict();
        $this->assertDatabaseHas('books', ['id' => $book->id, $foreignKey => $id]);
        $this->deleteJson("/api/books/{$book->id}")->assertNoContent();
        $this->deleteJson("/api/$resource/$id")->assertNoContent();
    }

    public static function invalidBooks(): array
    {
        return [
            'missing title' => [['title' => '  '], 'title'],
            'missing author' => [['author' => ''], 'author'],
            'zero pages' => [['totalPages' => 0], 'totalPages'],
            'too many pages' => [['totalPages' => 20001], 'totalPages'],
            'fractional pages' => [['totalPages' => 2.5], 'totalPages'],
            'negative current page' => [['currentPage' => -1], 'currentPage'],
            'current page past total' => [['currentPage' => 241], 'currentPage'],
            'unknown status' => [['status' => 'unknown'], 'status'],
            'want to read with progress' => [['currentPage' => 2], 'currentPage'],
            'finished before last page' => [['status' => 'finished'], 'currentPage'],
            'HTTP cover' => [['coverUrl' => 'http://example.com/capa.jpg'], 'coverUrl'],
            'cover with credentials' => [['coverUrl' => 'https://user:secret@example.com/capa.jpg'], 'coverUrl'],
            'cover with whitespace' => [['coverUrl' => 'https://example.com/capa azul.jpg'], 'coverUrl'],
            'overlong cover' => [['coverUrl' => 'https://example.com/'.str_repeat('a', 2048)], 'coverUrl'],
        ];
    }

    #[DataProvider('invalidBooks')]
    public function test_book_validation_rejects_invalid_data_before_persisting(array $changes, string $field): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/books', array_replace(self::bookPayload(), $changes))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('authors', 0);
        $this->assertDatabaseCount('genres', 0);
    }

    public function test_reaching_the_last_page_finishes_the_book_and_reuses_owned_catalogs(): void
    {
        $owner = User::factory()->create();
        $foreignAuthor = Author::factory()->create(['name' => 'Autoria de teste']);
        Sanctum::actingAs($owner);

        $created = $this->postJson('/api/books', array_replace(self::bookPayload(), ['currentPage' => 240, 'status' => 'reading']))
            ->assertCreated()->assertJsonPath('data.status', 'finished')->assertJsonPath('data.currentPage', 240);
        $this->assertNotSame((string) $foreignAuthor->id, $created->json('data.authorId'));
        $this->postJson('/api/books', self::bookPayload())->assertCreated();
        $this->assertSame(1, Author::query()->where('user_id', $owner->id)->count());
        $this->assertSame(1, Genre::query()->where('user_id', $owner->id)->count());
    }

    public function test_editing_and_clearing_cover_preserve_the_creation_and_remaining_book_data(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $payload = self::bookPayload();
        $created = $this->postJson('/api/books', $payload)->assertCreated();
        $id = $created->json('data.id');
        $this->travel(2)->seconds();

        $payload['coverUrl'] = 'https://example.com/nova%20capa.jpg?size=300';
        $this->putJson("/api/books/$id", $payload)->assertOk()->assertJsonPath('data.coverUrl', $payload['coverUrl'])
            ->assertJsonPath('data.createdAt', $created->json('data.createdAt'));
        $payload['coverUrl'] = '';
        $cleared = $this->putJson("/api/books/$id", $payload)->assertOk()->assertJsonPath('data.coverUrl', '')
            ->assertJsonPath('data.createdAt', $created->json('data.createdAt'));
        foreach (['title', 'author', 'genre', 'totalPages', 'currentPage', 'status', 'notes'] as $field) {
            $cleared->assertJsonPath("data.$field", $payload[$field]);
        }
        $this->assertNotSame($created->json('data.updatedAt'), $cleared->json('data.updatedAt'));
    }

    public function test_shelf_must_belong_to_owner_and_is_preserved_when_omitted_on_update(): void
    {
        $owner = User::factory()->create();
        $ownShelf = Shelf::factory()->create(['user_id' => $owner->id]);
        $foreignShelf = Shelf::factory()->create();
        Sanctum::actingAs($owner);
        $payload = self::bookPayload();
        $this->postJson('/api/books', [...$payload, 'shelfId' => $foreignShelf->id])
            ->assertUnprocessable()->assertJsonValidationErrors('shelfId');
        $this->assertDatabaseCount('books', 0);

        $created = $this->postJson('/api/books', [...$payload, 'shelfId' => $ownShelf->id])->assertCreated()
            ->assertJsonPath('data.shelfId', (string) $ownShelf->id);
        $id = $created->json('data.id');
        $this->putJson("/api/books/$id", [...$payload, 'title' => 'Título editado'])->assertOk()
            ->assertJsonPath('data.shelfId', (string) $ownShelf->id);
        $this->putJson("/api/books/$id", [...$payload, 'shelfId' => null])->assertOk()->assertJsonPath('data.shelfId', null);
    }

    private static function bookPayload(): array
    {
        return [
            'title' => 'Livro de teste',
            'author' => 'Autoria de teste',
            'genre' => 'Ficção',
            'totalPages' => 240,
            'currentPage' => 0,
            'status' => 'wantToRead',
            'notes' => 'Anotação preservada.',
            'coverUrl' => 'https://example.com/capa.jpg',
        ];
    }
}

<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Repositories\Contracts\AuthorRepositoryInterface;
use App\Repositories\Contracts\BookRepositoryInterface;
use App\Repositories\Contracts\GenreRepositoryInterface;
use App\Repositories\Contracts\ShelfRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function __construct(
        private BookRepositoryInterface $books,
        private AuthorRepositoryInterface $authors,
        private GenreRepositoryInterface $genres,
        private ShelfRepositoryInterface $shelves,
    ) {}

    public function all(int $userId): Collection
    {
        return $this->books->allForUser($userId);
    }

    public function get(int $userId, int $id): Book
    {
        return $this->books->findForUser($userId, $id);
    }

    public function create(int $userId, array $data): Book
    {
        return DB::transaction(function () use ($userId, $data): Book {
            return $this->books->createForUser($userId, $this->attributes($userId, $data));
        });
    }

    public function update(int $userId, int $id, array $data): Book
    {
        return DB::transaction(function () use ($userId, $id, $data): Book {
            $existing = $this->get($userId, $id);
            $attributes = $this->attributes($userId, $data);
            if (! array_key_exists('shelfId', $data)) {
                $attributes['shelf_id'] = $existing->shelf_id;
            }

            return $this->books->updateForUser($userId, $id, $attributes);
        });
    }

    public function delete(int $userId, int $id): void
    {
        $this->books->deleteForUser($userId, $id);
    }

    private function attributes(int $userId, array $data): array
    {
        $currentPage = (int) $data['currentPage'];
        $totalPages = (int) $data['totalPages'];
        $status = BookStatus::from($data['status']);
        if ($currentPage < 0 || $currentPage > $totalPages || $totalPages < 1 || $totalPages > 20000) {
            throw ValidationException::withMessages(['currentPage' => ['A página atual deve estar entre zero e o total de páginas.']]);
        }
        if ($status === BookStatus::WantToRead && $currentPage !== 0) {
            throw ValidationException::withMessages(['currentPage' => ['Um livro que você quer ler deve iniciar na página zero.']]);
        }
        if ($status === BookStatus::Finished && $currentPage !== $totalPages) {
            throw ValidationException::withMessages(['currentPage' => ['Um livro concluído deve estar na última página.']]);
        }
        if ($currentPage === $totalPages) {
            $status = BookStatus::Finished;
        }

        $shelfId = $data['shelfId'] ?? null;
        if ($shelfId !== null) {
            $shelfId = $this->shelves->findForUser($userId, (int) $shelfId)->getKey();
        }
        $author = $this->authors->firstOrCreateByName($userId, trim($data['author']));
        $genreName = trim($data['genre'] ?? '');
        $genre = $genreName === '' ? null : $this->genres->firstOrCreateByName($userId, $genreName);

        return [
            'title' => trim($data['title']),
            'author_id' => $author->getKey(),
            'genre_id' => $genre?->getKey(),
            'shelf_id' => $shelfId,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'status' => $status->value,
            'notes' => trim($data['notes'] ?? ''),
            'cover_url' => trim($data['coverUrl'] ?? ''),
        ];
    }
}

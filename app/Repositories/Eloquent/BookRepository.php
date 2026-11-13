<?php

namespace App\Repositories\Eloquent;

use App\Models\Book;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookRepository extends OwnedRepository implements BookRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Book);
    }

    protected function queryForUser(int $userId): Builder
    {
        return parent::queryForUser($userId)->with(['author', 'genre', 'shelf']);
    }

    public function createForUser(int $userId, array $attributes): Model
    {
        $book = parent::createForUser($userId, $attributes);

        return $this->findForUser($userId, $book->getKey());
    }
}

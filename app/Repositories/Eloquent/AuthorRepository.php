<?php

namespace App\Repositories\Eloquent;

use App\Models\Author;
use App\Repositories\Contracts\AuthorRepositoryInterface;

class AuthorRepository extends CatalogRepository implements AuthorRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Author);
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Models\Genre;
use App\Repositories\Contracts\GenreRepositoryInterface;

class GenreRepository extends CatalogRepository implements GenreRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Genre);
    }
}

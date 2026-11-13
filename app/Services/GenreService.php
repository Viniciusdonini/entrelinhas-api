<?php

namespace App\Services;

use App\Repositories\Contracts\GenreRepositoryInterface;

class GenreService extends CatalogService
{
    public function __construct(GenreRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}

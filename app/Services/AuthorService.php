<?php

namespace App\Services;

use App\Repositories\Contracts\AuthorRepositoryInterface;

class AuthorService extends CatalogService
{
    public function __construct(AuthorRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}

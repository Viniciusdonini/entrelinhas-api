<?php

namespace App\Services;

use App\Repositories\Contracts\ShelfRepositoryInterface;

class ShelfService extends CatalogService
{
    public function __construct(ShelfRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}

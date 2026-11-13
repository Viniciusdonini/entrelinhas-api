<?php

namespace App\Repositories\Eloquent;

use App\Models\Shelf;
use App\Repositories\Contracts\ShelfRepositoryInterface;

class ShelfRepository extends CatalogRepository implements ShelfRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Shelf);
    }
}

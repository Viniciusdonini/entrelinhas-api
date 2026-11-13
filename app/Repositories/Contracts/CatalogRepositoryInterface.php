<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CatalogRepositoryInterface extends OwnedRepositoryInterface
{
    public function firstOrCreateByName(int $userId, string $name): Model;

    public function hasBooks(int $userId, int $id): bool;
}

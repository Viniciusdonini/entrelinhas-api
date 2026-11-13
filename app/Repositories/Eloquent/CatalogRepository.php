<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class CatalogRepository extends OwnedRepository implements CatalogRepositoryInterface
{
    public function firstOrCreateByName(int $userId, string $name): Model
    {
        return $this->model->newQuery()->firstOrCreate(['user_id' => $userId, 'name' => $name]);
    }

    public function hasBooks(int $userId, int $id): bool
    {
        return $this->queryForUser($userId)->whereKey($id)->whereHas('books')->exists();
    }
}

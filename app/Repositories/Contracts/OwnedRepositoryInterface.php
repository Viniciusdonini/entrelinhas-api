<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface OwnedRepositoryInterface
{
    public function allForUser(int $userId): Collection;

    public function findForUser(int $userId, int $id): Model;

    public function createForUser(int $userId, array $attributes): Model;

    public function updateForUser(int $userId, int $id, array $attributes): Model;

    public function deleteForUser(int $userId, int $id): void;
}

<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\OwnedRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class OwnedRepository implements OwnedRepositoryInterface
{
    public function __construct(protected Model $model) {}

    protected function queryForUser(int $userId): Builder
    {
        return $this->model->newQuery()->where('user_id', $userId);
    }

    public function allForUser(int $userId): Collection
    {
        return $this->queryForUser($userId)->orderByDesc('id')->get();
    }

    public function findForUser(int $userId, int $id): Model
    {
        // A foreign ID behaves exactly like an absent ID.
        return $this->queryForUser($userId)->findOrFail($id);
    }

    public function createForUser(int $userId, array $attributes): Model
    {
        // The authenticated owner always wins over any supplied attributes.
        return $this->model->newQuery()->create(array_replace($attributes, ['user_id' => $userId]));
    }

    public function updateForUser(int $userId, int $id, array $attributes): Model
    {
        $model = $this->findForUser($userId, $id);
        unset($attributes['user_id']);
        $model->update($attributes);

        return $this->findForUser($userId, $id);
    }

    public function deleteForUser(int $userId, int $id): void
    {
        $this->findForUser($userId, $id)->delete();
    }
}

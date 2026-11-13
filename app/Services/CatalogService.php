<?php

namespace App\Services;

use App\Exceptions\ResourceInUseException;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class CatalogService
{
    public function __construct(protected CatalogRepositoryInterface $repository) {}

    public function all(int $userId): Collection
    {
        return $this->repository->allForUser($userId);
    }

    public function get(int $userId, int $id): Model
    {
        return $this->repository->findForUser($userId, $id);
    }

    public function create(int $userId, array $data): Model
    {
        return $this->repository->createForUser($userId, $this->normalize($data));
    }

    public function update(int $userId, int $id, array $data): Model
    {
        return $this->repository->updateForUser($userId, $id, $this->normalize($data));
    }

    public function delete(int $userId, int $id): void
    {
        $this->repository->findForUser($userId, $id);
        if ($this->repository->hasBooks($userId, $id)) {
            throw new ResourceInUseException('Este cadastro está ligado a um livro. Altere ou exclua o livro antes de removê-lo.');
        }
        $this->repository->deleteForUser($userId, $id);
    }

    private function normalize(array $data): array
    {
        return array_map(fn (mixed $value) => $value === null ? '' : (is_string($value) ? trim($value) : $value), $data);
    }
}

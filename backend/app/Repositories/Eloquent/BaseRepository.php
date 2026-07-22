<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function find(int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function findByUuid(string $uuid): ?Model
    {
        return $this->model->newQuery()->where('uuid', $uuid)->first();
    }

    public function all(): Collection
    {
        return $this->model->newQuery()->get();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->model->newQuery(), $filters)->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * Override in concrete repositories to support ?search=&status=&sort= style filters.
     */
    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['search']) && in_array('name', $this->model->getFillable())) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['status']) && in_array('status', $this->model->getFillable())) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['sort'])) {
            [$column, $direction] = array_pad(explode(':', $filters['sort']), 2, 'asc');
            $query->orderBy($column, $direction);
        } else {
            $query->latest();
        }

        return $query;
    }
}

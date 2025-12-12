<?php

namespace App\Core\Shared\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->getModelClass());
    }

    /**
     * Get the model class for this repository
     */
    abstract protected function getModelClass(): string;

    /**
     * Get a new query builder instance
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Find a record by ID
     */
    public function find(string $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * Find a record by ID or fail
     */
    public function findOrFail(string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * Update a record
     */
    public function update(string $id, array $data): bool
    {
        return $this->query()->where('id', $id)->update($data) > 0;
    }

    /**
     * Delete a record
     */
    public function delete(string $id): bool
    {
        return $this->query()->where('id', $id)->delete() > 0;
    }

    /**
     * Check if a record exists
     */
    public function exists(string $id): bool
    {
        return $this->query()->where('id', $id)->exists();
    }

    /**
     * Count records
     */
    public function count(): int
    {
        return $this->query()->count();
    }
}
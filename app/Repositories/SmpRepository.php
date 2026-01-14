<?php

namespace App\Repositories;

use App\Interfaces\SmpRepositoryInterface;
use App\Models\SmpModel;

class SmpRepository implements SmpRepositoryInterface
{
    private SmpModel $model;

    public function __construct()
    {
        $this->model = new SmpModel();
    }

    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    public function findByInn(string $inn): ?array
    {
        return $this->model->where('inn', $inn)->first();
    }

    /**
     * @throws \ReflectionException
     */
    public function create(array $data): int
    {
        return $this->model->insert($data);
    }
}
<?php

namespace App\Interfaces;

interface SmpRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findByInn(string $inn): ?array;
    public function create(array $data): int;
}
<?php

namespace Domain\Repositories;
use Domain\Entities\Major;

interface MajorRepositoryInterface {
    public function insert(Major $major): void;
    public function findById(string $id): ?Major;
    public function findByNameAndAdmissionYear(string $name, int $admissionYear): ?Major;
    public function findAll(): array;
}

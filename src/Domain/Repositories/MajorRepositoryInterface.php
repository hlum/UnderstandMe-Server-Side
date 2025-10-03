<?php

namespace Domain\Repositories;
use Domain\Entities\Major;

interface MajorRepositoryInterface {
    public function insert(Major $major): void;
    public function findById(string $id): ?Major;
    public function findByTeacherId(string $teacherId): array;
    public function findByClassNameAndAdmissionYear(string $className, int $admissionYear): array;
}

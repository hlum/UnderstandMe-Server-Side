<?php

namespace Domain\Repositories;
use Domain\Entities\ClassEntity;

interface MajorRepositoryInterface {
    public function insert(ClassEntity $major): void;
    public function findById(string $id): ?ClassEntity;
    public function findByTeacherId(string $teacherId): array;
    public function findByClassNameAndAdmissionYear(string $className, int $admissionYear): array;
}

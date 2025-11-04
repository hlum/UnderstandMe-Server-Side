<?php

namespace Domain\Repositories;
use Domain\Entities\ClassEntity;

interface ClassRepositoryInterface
{
    public function insert(ClassEntity $class): void;
    public function findById(string $id): ?ClassEntity;
    public function findByTeacherId(string $teacherId): array;
    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array;
    public function update(ClassEntity $class): void;
}

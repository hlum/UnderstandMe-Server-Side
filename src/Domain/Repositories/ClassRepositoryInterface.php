<?php

namespace Domain\Repositories;
use Domain\Entities\ClassEntity;

interface ClassRepositoryInterface
{
    public function insert(ClassEntity $class): void;
    public function findById(string $id): ?ClassEntity;

    /**
     * Summary of findByIDs
     * @param string[] $ids
     * @return ClassEntity[]
     */
    public function findByIDs(array $ids): array;
    public function findByClassCode(string $classCode): ?ClassEntity;

    /**
     * @return ClassEntity[]
     */
    public function findByTeacherId(string $teacherId): array;

    /**
     * @return ClassEntity[]
     */
    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array;
    
    public function update(ClassEntity $class): void;
}

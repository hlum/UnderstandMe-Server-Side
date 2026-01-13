<?php
// src/Domain/Repositories/UserRepositoryInterface.php

namespace Domain\Repositories;

use Domain\Entities\User;

interface UserRepositoryInterface
{

    
    public function insert(User $user): void;


    public function findByEmail(string $email): ?User;


    public function findById(string $id): ?User;

    /**
     * Summary of findByIDs
     * @param string[] $ids
     * @return User[]
     */
    public function findByIDs(array $ids): array;


    public function findByStudentCode(string $studentCode): ?User;


    /**
     * @return User[]
     */
    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array;

    public function deleteByID(string $user_id): void;

}
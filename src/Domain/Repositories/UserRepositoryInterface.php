<?php
// src/Domain/Repositories/UserRepositoryInterface.php

namespace Domain\Repositories;

use Domain\Entities\User;

interface UserRepositoryInterface {
    public function insert(User $user): void;
    public function findByEmail(string $email): ?User;
    public function findById(string $id): ?User;
    public function findByStudentCode(string $studentCode): ?User;
    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array;
    public function updateFcmToken(string $userId, ?string $fcmToken): void;

}
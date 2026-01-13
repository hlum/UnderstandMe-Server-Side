<?php

// src/Application/UseCases/UserUseCase.php
namespace Application\UseCases;

use Application\CustomExceptions\NotFoundException;
use Application\CustomExceptions\ValidationException;
use Domain\Entities\User;
use Domain\Entities\Role;
use Domain\Repositories\UserRepositoryInterface;

class UserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerUser(string $id, string $name, string $email, Role $role, ?string $photoURL, ?string $studentCode, ?int $admissionYear, ?string $majorCode): User
    {

        if ($photoURL != null && !filter_var($photoURL, FILTER_VALIDATE_URL)) {
            throw new ValidationException("無効なphoto_url形式です。");
        }

        // 既に存在するメールアドレスか確認
        if ($this->userRepository->findById($id) !== null) {
            throw new ValidationException("このユーザーIDは既に登録されています", 200);
        }
        // 既に存在するメールアドレスか確認
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new ValidationException("このメールアドレスは既に登録されています", 200);
        }
        // 既に存在する学生コードか確認
        if ($role->getValue() === 'student' && $this->userRepository->findByStudentCode($studentCode) !== null) {
            throw new ValidationException("この学生コードは既に登録されています", 200);
        }

        // 新しいユーザーを作成
        $user = User::createNew($id, $name, $email, $role, $studentCode, $admissionYear, $majorCode, $photoURL);

        // ユーザーをリポジトリに保存
        $this->userRepository->insert($user);

        return $user;
    }

    public function findById(string $user_id): ?User
    {
        $user = $this->userRepository->findById($user_id);
        return $user;
    }


    /**
     * @param array $ids
     * @return User[]
     */
    public function findByIDs(array $ids): array {
        if(empty($ids)) { return [];}

        $users = $this->userRepository->findByIDs($ids);

        return $users;
    }

    
    public function deleteUserByID(string $user_id): void
    {
        $user = $this->userRepository->findById($user_id);
        if ($user === null) {
            throw new NotFoundException(message: "指定されたユーザーIDのユーザーが存在しません");
        }

        $this->userRepository->deleteByID($user_id);
    }


    public function findByEmail(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new NotFoundException(message: "指定されたユーザーIDのユーザーが存在しません");
        }

        return $user;
    }


    public function findByStudentCode(string $studentCode): User
    {
        $user = $this->userRepository->findByStudentCode($studentCode);
        if ($user === null) {
            throw new NotFoundException(message: "指定された学生コードのユーザーが存在しません");
        }

        return $user;
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array
    {
        $user = $this->userRepository->findByMajorCodeAndAdmissionYear($majorCode, $admissionYear);
        if ($user === null) {
            throw new NotFoundException(message: "指定された学生コードのユーザーが存在しません");
        }

        return $user;
    }
}
<?php

// src/Application/UseCases/UserUseCase.php
namespace Application\UseCases;

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
            throw new \InvalidArgumentException("無効なphoto_url形式です。");
        }

        // 既に存在するメールアドレスか確認
        if ($this->userRepository->findById($id) !== null) {
            throw new \InvalidArgumentException("このユーザーIDは既に登録されています", 200);
        }
        // 既に存在するメールアドレスか確認
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new \InvalidArgumentException("このメールアドレスは既に登録されています", 200);
        }
        // 既に存在する学生コードか確認
        if ($role->getValue() === 'student' && $this->userRepository->findByStudentCode($studentCode) !== null) {
            throw new \InvalidArgumentException("この学生コードは既に登録されています", 200);
        }

        // 学生コードが既に存在するか確認(RoleがStudentの場合のみ)
        if ($role->getValue() === 'student' && $studentCode !== null) {
            if ($this->userRepository->findByStudentCode($studentCode) !== null) {
                throw new \InvalidArgumentException("この学生コードは既に登録されています");
            }
        }

        // 新しいユーザーを作成
        $user = User::createNew($id, $name, $email, $role, $studentCode, $admissionYear, $majorCode, $photoURL);

        // ユーザーをリポジトリに保存
        $this->userRepository->insert($user);

        return $user;
    }

    public function findById(string $user_id): User
    {
        $user = $this->userRepository->findById($user_id);
        if ($user === null) {
            throw new \InvalidArgumentException(message: "指定されたユーザーIDのユーザーが存在しません", code: 200);
        }

        return $user;
    }


    public function findByEmail(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \InvalidArgumentException(message: "指定されたユーザーIDのユーザーが存在しません", code: 200);
        }

        return $user;
    }


    public function findByStudentCode(string $studentCode): User
    {
        $user = $this->userRepository->findByStudentCode($studentCode);
        if ($user === null) {
            throw new \InvalidArgumentException(message: "指定された学生コードのユーザーが存在しません", code: 200);
        }

        return $user;
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array
    {
        $user = $this->userRepository->findByMajorCodeAndAdmissionYear($majorCode, $admissionYear);
        if ($user === null) {
            throw new \InvalidArgumentException(message: "指定された学生コードのユーザーが存在しません", code: 200);
        }

        return $user;
    }
}
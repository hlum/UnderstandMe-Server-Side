<?php

// src/Application/UseCases/UserUseCase.php
namespace Application\UseCases;

use Domain\Entities\User;
use Domain\Entities\Role;
use Domain\Repositories\UserRepositoryInterface;

class UserUseCase {
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function registerUser(string $id, string $email, Role $role, ?string $studentCode, ?int $admissionYear, ?string $className, ?string $fcmToken): User {
        // 学校のメールか確認
        if (!str_ends_with($email, '@jec.ac.jp')) {
            throw new \InvalidArgumentException("学校のメールアドレスではありません。");
        }
        // 既に存在するメールアドレスか確認
        if ($this->userRepository->findById($id) !== null) {
            throw new \InvalidArgumentException("このユーザーIDは既に登録されています");
        }
         // 既に存在するメールアドレスか確認
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new \InvalidArgumentException("このメールアドレスは既に登録されています");
        }
        // 既に存在する学生コードか確認
        if ($this->userRepository->findByStudentCode($studentCode) !== null) {
            throw new \InvalidArgumentException("この学生コードは既に登録されています");
        }

        // 学生コードが既に存在するか確認(RoleがStudentの場合のみ)
        if ($role === 'student' && $studentCode !== null) {
            if ($this->userRepository->findByStudentCode($studentCode) !== null) {
                throw new \InvalidArgumentException("この学生コードは既に登録されています");
            }
        }

        // 新しいユーザーを作成
        $user = User::createNew($id, $email, $role, $studentCode, $admissionYear, $className, $fcmToken);

        // ユーザーをリポジトリに保存
        $this->userRepository->insert($user);

        return $user;
    }

    public function updateFcmToken(string $userId, ?string $fcmToken): void {
        // ユーザーが存在するか確認
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \InvalidArgumentException("指定されたユーザーIDのユーザーが存在しません");
        }

        // FCMトークンを更新
        $this->userRepository->updateFcmToken($userId, $fcmToken);
    }
}
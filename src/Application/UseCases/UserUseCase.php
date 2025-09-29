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
            throw new \InvalidArgumentException( "指定されたユーザーIDのユーザーが存在しません");
        }


        if($fcmToken == null) {
            throw new \InvalidArgumentException("無効なfcm_tokenです。");
        }

        // FCMトークンを更新
        $this->userRepository->updateFcmToken($userId, $fcmToken);
    }

    public function findById(string $user_id):User {
        $user = $this->userRepository->findById($user_id);
        if($user === null) {
            throw new \InvalidArgumentException(message: "指定されたユーザーIDのユーザーが存在しません");
        }

        return $user;
    }


    public function findByEmail(string $email): User {
        $user = $this->userRepository->findByEmail($email);
        if($user === null) {
            throw new \InvalidArgumentException(message: "指定されたユーザーIDのユーザーが存在しません");
        }

        return $user;
    }


    public function findByStudentCode(string $studentCode): User {
        $user = $this->userRepository->findByStudentCode($studentCode);
        if($user === null) {
            throw new \InvalidArgumentException(message: "指定された学生コードのユーザーが存在しません");
        }

        return $user;
    }


    public function findByClassNameAndAdmissionYear(string $className, int $admissionYear): array {
        return $this->userRepository->findByClassNameAndAdmissionYear($className, $admissionYear);
    }
}
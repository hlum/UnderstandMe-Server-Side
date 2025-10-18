<?php

// src/Infrastructure/Persistence/MySQLUserRepository.php
namespace Infrastructure\Persistence;

use Domain\Entities\User;
use Domain\Repositories\UserRepositoryInterface;
use mysqli;
use mysqli_result;

class MySQLUserRepository implements UserRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;

        if ($this->connection->connect_error) {
            throw new \RuntimeException('データベース接続エラー \n 詳細 \n' . $this->connection->connect_error);
        }
    }

    public function insert(User $user): void
    {
        $query = "INSERT INTO users (id, email, role, photo_url, student_code, admission_year, major_code, fcm_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $types = 'ssssssss';
        $params = [$user->id, $user->email, $user->role->getValue(), $user->photoURL, $user->studentCode, $user->admissionYear, $user->majorCode, $user->fcmToken];
        $error_message = 'ユーザーの保存に失敗しました';
        $this->executeQuery($query, $types, $params, $error_message);
    }

    public function findByEmail(string $email): ?User
    {
        $query = "SELECT * FROM users WHERE email = ?";
        $types = 's';
        $params = [$email];
        $error_message = 'メールアドレスによるユーザーの検索に失敗しました';

        $result = $this->executeQuery($query, $types, $params, $error_message);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }
        return User::fromDbRow($row);
    }


    public function findById(string $id): ?User
    {
        $query = "SELECT * FROM users WHERE id = ?";
        $types = 's';
        $params = [$id];
        $error_message = 'IDによるユーザーの検索に失敗しました';

        $result = $this->executeQuery($query, $types, $params, $error_message);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return User::fromDbRow($row);
    }


    public function findByStudentCode(string $studentCode): ?User
    {
        $query = "SELECT * FROM users WHERE student_code = ?";
        $types = 's';
        $params = [$studentCode];
        $error_message = '学生コードによるユーザーの検索に失敗しました';

        $result = $this->executeQuery($query, $types, $params, $error_message);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return User::fromDbRow($row);
    }


    public function findByMajorCodeAndAdmissionYear(string $majorCode, int $admissionYear): array
    {
        $query = "SELECT * FROM users WHERE major_code = ? AND admission_year = ?";
        $types = 'si';
        $params = [$majorCode, $admissionYear];
        $error_message = '専攻コードと入学年によるユーザーの検索に失敗しました';

        $result = $this->executeQuery($query, $types, $params, $error_message);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = User::fromDbRow($row);
        }

        return $users;
    }


    public function updateFcmToken(string $userId, ?string $fcmToken): void
    {
        $query = "UPDATE users SET fcm_token = ? WHERE id = ?";
        $types = 'ss';
        $params = [$fcmToken, $userId];
        $error_message = 'FCMトークンの更新に失敗しました';
        $this->executeQuery($query, $types, $params, $error_message);
    }


    private function executeQuery(
        string $query,
        string $types,
        array $params,
        string $error_message
    ): mysqli_result|bool {
        $stmt = $this->connection->prepare($query);

        if ($stmt === false) {
            throw new \RuntimeException('ステートメントの準備に失敗しました。詳細: ' . $this->connection->error);
        }

        $stmt->bind_param($types, ...$params);
        if ($stmt === false) {
            throw new \RuntimeException('パラメータのバインドに失敗しました。詳細: ' . $this->connection->error);
        }

        if (!$stmt->execute()) {
            throw new \RuntimeException('クエリの実行に失敗しました。詳細: ' . $stmt->error);
        }

        return $stmt->get_result();
    }
}
<?php
// src/Domain/Entities/User.php
namespace Domain\Entities;
use JsonSerializable;

// DBに保存　-> $user->role->value.
// DBから取得しUserに変換 -> Role::from($row['role']).

class User implements JsonSerializable {
    public string $id;
    public string $email;
    public ?string $photoURL;
    public Role $role;
    public ?string $studentCode;       // 学生のみ
    public ?int $admissionYear;        // 学生のみ
    public ?string $majorCode;         // 学生のみ
    public ?string $fcmToken;
    public \DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $email,
        ?string $photoURL,
        Role $role,
        ?string $studentCode,
        ?int $admissionYear,
        ?string $majorCode,
        ?string $fcmToken,
        \DateTimeImmutable $createdAt
    ) {
        // ドメインレベルでもテーブル制約を適用する
        if ($role->getValue() === 'student' && ($studentCode === null || $admissionYear === null || $majorCode === null)) {
            throw new \InvalidArgumentException("学生はstudent_code、admission_year、class_nameを持つ必要があります");
        }
        if ($role->getValue() === 'teacher' && ($studentCode !== null || $admissionYear !== null || $majorCode !== null)) {
            throw new \InvalidArgumentException("教師はstudent_code、admission_year、class_nameを持つべきではありません");
        }

        $this->id = $id;
        $this->email = $email;
        $this->photoURL = $photoURL;
        $this->role = $role;
        $this->studentCode = $studentCode;
        $this->admissionYear = $admissionYear;
        $this->majorCode = $majorCode;
        $this->fcmToken = $fcmToken;
        $this->createdAt = $createdAt;
    }


    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role instanceof Role ? $this->role->getValue() : $this->role,
            'student_code' => $this->studentCode,
            'photo_url' => $this->photoURL,
            'admission_year' => $this->admissionYear,
            'major_code' => $this->majorCode,
            'fcm_token' => $this->fcmToken,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }


    // ファクトリーメソッド：新規ユーザー用（created_at = now）
    public static function createNew(
        string $id,
        string $email,
        Role $role,
        ?string $studentCode,
        ?int $admissionYear,
        ?string $majorCode,
        ?string $fcmToken,
        ?string $photoURL
    ): self {
        return new self(
            $id,
            $email,
            $photoURL,
            $role,
            $studentCode,
            $admissionYear,
            $majorCode,
            $fcmToken,
            new \DateTimeImmutable()
        );
    }

    // ファクトリーメソッド：DBからの復元用
    public static function fromDbRow(array $row): self {
        return new self(
            $row['id'],
            $row['email'],
            $row['photo_url'] ?? null,
            Role::from($row['role']),
            $row['student_code'] ?? null,
            $row['admission_year'] !== null ? (int)$row['admission_year'] : null,
            $row['major_code'] ?? null,
            $row['fcm_token'] ?? null,
            new \DateTimeImmutable($row['created_at'])
        );
    }
}

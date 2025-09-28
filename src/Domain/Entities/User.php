<?php
// src/Domain/Entities/User.php
namespace Domain\Entities;

// DBに保存　-> $user->role->value.
// DBから取得しUserに変換 -> Role::from($row['role']).
class Role {
    private string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public function getValue(): string {
        return $this->value;
    }

    public static function from(string $value): self {
        if ($value === 'teacher') {
            return new self('teacher');
        } elseif ($value === 'student') {
            return new self('student');
        } else {
            throw new \InvalidArgumentException("Invalid role value: $value");
        }
    }
}

class User {
    public string $id;
    public string $email;
    public Role $role;
    public ?string $studentCode;       // 学生のみ
    public ?int $admissionYear;        // 学生のみ
    public ?string $className;         // 学生のみ
    public ?string $fcmToken;
    public \DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $email,
        Role $role,
        ?string $studentCode,
        ?int $admissionYear,
        ?string $className,
        ?string $fcmToken,
        \DateTimeImmutable $createdAt
    ) {
        // ドメインレベルでもテーブル制約を適用する
        if ($role->getValue() === 'student' && ($studentCode === null || $admissionYear === null || $className === null)) {
            throw new \InvalidArgumentException("学生はstudent_code、admission_year、class_nameを持つ必要があります");
        }
        if ($role->getValue() === 'teacher' && ($studentCode !== null || $admissionYear !== null || $className !== null)) {
            throw new \InvalidArgumentException("教師はstudent_code、admission_year、class_nameを持つべきではありません");
        }

        $this->id = $id;
        $this->email = $email;
        $this->role = $role;
        $this->studentCode = $studentCode;
        $this->admissionYear = $admissionYear;
        $this->className = $className;
        $this->fcmToken = $fcmToken;
        $this->createdAt = $createdAt;
    }

    // ファクトリーメソッド：新規ユーザー用（created_at = now）
    public static function createNew(
        string $id,
        string $email,
        Role $role,
        ?string $studentCode,
        ?int $admissionYear,
        ?string $className,
        ?string $fcmToken
    ): self {
        return new self(
            $id,
            $email,
            $role,
            $studentCode,
            $admissionYear,
            $className,
            $fcmToken,
            new \DateTimeImmutable()
        );
    }

    // ファクトリーメソッド：DBからの復元用
    public static function fromDbRow(array $row): self {
        return new self(
            $row['id'],
            $row['email'],
            Role::from($row['role']),
            $row['student_code'] ?? null,
            $row['admission_year'] !== null ? (int)$row['admission_year'] : null,
            $row['class_name'] ?? null,
            $row['fcm_token'] ?? null,
            new \DateTimeImmutable($row['created_at'])
        );
    }
}

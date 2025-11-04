<?php


namespace Domain\Entities;

use DateTimeImmutable;
use JsonSerializable;

// +----------------+--------------+------+-----+-------------------+-------------------+
// | Field          | Type         | Null | Key | Default           | Extra             |
// +----------------+--------------+------+-----+-------------------+-------------------+
// | id             | char(36)     | NO   | PRI | NULL              |                   |
// | name           | varchar(100) | NO   | MUL | NULL              |                   |
// | admission_year | int          | NO   |     | NULL              |                   |
// | major_code     | varchar(10)  | NO   |     | NULL              |                   |
// | created_at     | timestamp    | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
// +----------------+--------------+------+-----+-------------------+-------------------+
// 5 rows in set (0.01 sec)
class ClassEntity implements JsonSerializable
{
    public string $id;
    public string $teacher_id;
    public string $name;
    public int $admissionYear;
    public string $majorCode;
    public DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $teacher_id,
        string $name,
        int $admissionYear,
        string $majorCode,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->teacher_id = $teacher_id;
        $this->name = $name;
        $this->admissionYear = $admissionYear;
        $this->majorCode = $majorCode;
        $this->createdAt = $createdAt;
    }

    public static function createNew(
        string $teacher_id,
        string $name,
        int $admissionYear,
        string $majorCode,
        ?string $id = null
    ): self {
        
        if($id === null) {
            $id = bin2hex(random_bytes(16));
        }

        return new self(
            $id,
            $teacher_id,
            $name,
            $admissionYear,
            $majorCode,
            new DateTimeImmutable()
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['teacher_id'],
            $row['name'],
            (int) $row['admission_year'],
            $row['major_code'],
            new DateTimeImmutable($row['created_at'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'name' => $this->name,
            'admission_year' => $this->admissionYear,
            'major_code' => $this->majorCode,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
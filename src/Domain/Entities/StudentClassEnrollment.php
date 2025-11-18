<?php

namespace Domain\Entities;

use DateTimeImmutable;
use JsonSerializable;


class StudentClassEnrollment implements JsonSerializable
{
    public string $id;
    public string $studentID;
    public string $classID;
    public DateTimeImmutable $enrolledAt;

    private function __construct(
        string $id,
        string $studentID,
        string $classID,
        DateTimeImmutable $enrolledAt
    ) {
        $this->id = $id;
        $this->studentID = $studentID;
        $this->classID = $classID;
        $this->enrolledAt = $enrolledAt;
    }

    public static function createNew(
        string $studentID,
        string $classID
    ): self {
        return new self(
            id: uniqid('enroll_', true),
            studentID: $studentID,
            classID: $classID,
            enrolledAt: new DateTimeImmutable()
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            id: $row['id'],
            studentID: $row['student_id'],
            classID: $row['class_id'],
            enrolledAt: new DateTimeImmutable($row['enrolled_at'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentID,
            'class_id' => $this->classID,
            'enrolled_at' => $this->enrolledAt->format('Y-m-d H:i:s'),
        ];
    }
}
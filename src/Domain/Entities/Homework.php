<?php

namespace Domain\Entities;

use JsonSerializable;
use DateTimeImmutable;

class Homework implements JsonSerializable
{
    public string $id;
    public string $teacherID;
    public string $classID;
    public string $title;
    public ?string $description;
    public ?DateTimeImmutable $dueDate;
    public DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $teacherID,
        string $classID,
        string $title,
        ?string $description,
        ?DateTimeImmutable $dueDate,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->teacherID = $teacherID;
        $this->classID = $classID;
        $this->title = $title;
        $this->description = $description;
        $this->dueDate = $dueDate;
        $this->createdAt = $createdAt;
    }


    public static function createNew(
        string $teacherID,
        string $classID,
        string $title,
        ?string $description,
        ?DateTimeImmutable $dueDate
    ): self {
        return new self(
            bin2hex(random_bytes(16)),
            $teacherID,
            $classID,
            $title,
            $description,
            $dueDate,
            new DateTimeImmutable()
        );
    }


    public static function fromDBRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['teacher_id'],
            $row['class_id'],
            $row['title'],
            $row['description'],
            isset($row['due_date']) ? new DateTimeImmutable($row['due_date']) : null,
            new DateTimeImmutable($row['created_at'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacherID,
            'class_id' => $this->classID,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->dueDate ? $this->dueDate->format('Y-m-d H:i:s') : null,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}

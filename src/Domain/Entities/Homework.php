<?php

namespace Domain\Entities;

use JsonSerializable;
use DateTimeImmutable;

class Homework implements JsonSerializable{
    public string $id;
    public string $teacherID;
    public string $majorID;
    public string $title;
    public string $description;
    public DateTimeImmutable $dueDate;
    public DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $teacherID,
        string $majorID,
        string $title,
        string $description, 
        DateTimeImmutable $dueDate, 
        DateTimeImmutable $createdAt
        ) {
        $this->id = $id;
        $this->teacherID = $teacherID;
        $this->majorID = $majorID;
        $this->title = $title;
        $this->description = $description;
        $this->dueDate = $dueDate;
        $this->createdAt = $createdAt;
    }


    public static function fromDBRow(array $row): self {
        return new self(
            $row['id'],
            $row['teacher_id'],
            $row['major_id'],
            $row['title'],
            $row['description'],
            $row['due_date'],
            $row['created_at']
        );
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacherID,
            'major_id' => $this->majorID,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->dueDate->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}

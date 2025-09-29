<?php

namespace Domain\Entities;

use JsonSerializable;

class Homework implements JsonSerializable{
    private $id;
    private $teacherID;
    private $majorID;
    private $title;
    private $description;
    private $dueDate;
    private $createdAt;

    public function __construct($id, $teacherID, $majorID, $title, $description, $dueDate, $createdAt) {
        $this->id = $id;
        $this->teacherID = $teacherID;
        $this->majorID = $majorID;
        $this->title = $title;
        $this->description = $description;
        $this->dueDate = $dueDate;
        $this->createdAt = $createdAt;
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

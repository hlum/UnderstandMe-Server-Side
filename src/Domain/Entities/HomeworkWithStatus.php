<?php

namespace Domain\Entities;

use JsonSerializable;


class HomeworkWithStatus implements JsonSerializable 
{
     public string $id;
     public string $title;
     public string $classID;
     public ?string $description;
     public ?string $dueDate;
     public ?string $githubFileLink;
     public ?string $jobStatus;
     public string $submissionState;
     public string $userID;
     public string $userEmail;
     public string $userStudentID;
     public ?int $score;


 private function __construct(
    string $id,
    string $title,
    string $classID,
    ?string $description,
    ?string $dueDate,
    ?string $githubFileLink,
    ?string $jobStatus,
    string $submissionState,
    string $userID,
    string $userEmail,
    string $userStudentID,
    ?int $score = null
) {
    $this->id = $id;
    $this->title = $title;
    $this->classID = $classID;
    $this->description = $description;
    $this->dueDate = $dueDate;
    $this->githubFileLink = $githubFileLink;
    $this->jobStatus = $jobStatus;
    $this->submissionState = $submissionState;
    $this->userID = $userID;
    $this->userEmail = $userEmail;
    $this->userStudentID = $userStudentID;
    $this->score = $score;
}



 public static function fromDBRow(array $row): self
{
    return new self(
        id: $row['homework_id'],
        title: $row['homework_title'],
        classID: $row['class_id'],
        description: $row['description'],
        dueDate: $row['due_date'] ?? null,
        githubFileLink: $row['github_file_link'] ?? null,
        jobStatus: $row['job_status'],
        submissionState: $row['submission_state'],
        userID: $row['user_id'],
        userEmail: $row['user_email'],
        userStudentID: $row['user_student_id'] ?? '',
        score: isset($row['score']) ? (int)$row['score'] : null
    );
}


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'class_id' => $this->classID,
            'description' => $this->description,
            'due_date' => $this->dueDate,
            'github_file_link' => $this->githubFileLink,
            'job_status' => $this->jobStatus,
            'submission_state' => $this->submissionState,
            'user_id' => $this->userID,
            'user_email' => $this->userEmail,
            'user_student_id' => $this->userStudentID,
            'score' => $this->score,
        ];
    }
}

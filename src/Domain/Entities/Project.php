<?php

namespace Domain\Entities;
use \DateTimeImmutable;
use \JsonSerializable;


class Project implements JsonSerializable
{
    public string $id;
    public string $userID;
    public string $homeworkID;
    public string $githubFileLink;
    public DateTimeImmutable $createdAt;


    private function __construct(
        string $id,
        string $userID,
        string $homeworkID,
        string $githubFileLink,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->userID = $userID;
        $this->homeworkID = $homeworkID;
        $this->githubFileLink = $githubFileLink;
        $this->createdAt = $createdAt;
    }

    public static function createNew(
        string $userID,
        string $homeworkID,
        string $githubFileLink
    ): self {
        return new self(
            bin2hex(random_bytes(16)),
            $userID,
            $homeworkID,
            $githubFileLink,
            new DateTimeImmutable()
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['user_id'],
            $row['homework_id'],
            $row['github_file_link'],
            new DateTimeImmutable($row['created_at'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userID,
            'homework_id' => $this->homeworkID,
            'github_file_link' => $this->githubFileLink,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
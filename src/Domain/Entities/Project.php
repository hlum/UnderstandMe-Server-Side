<?php

namespace Domain\Entities;
use \DateTimeImmutable;
use \JsonSerializable;


class Project implements JsonSerializable {
    public string $id;
    public string $userId;
    public string $homeworkId;
    public string $githubFileLink;
    public DateTimeImmutable $createdAt;


    private function __construct(
        string $id,
        string $userId,
        string $homeworkId,
        string $githubFileLink,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->homeworkId = $homeworkId;
        $this->githubFileLink = $githubFileLink;
        $this->createdAt = $createdAt;
    }

    public static function createNew(
        string $userId,
        string $homeworkId,
        string $githubFileLink
    ): self {
        return new self(
            bin2hex(random_bytes(16)),
            $userId,
            $homeworkId,
            $githubFileLink,
            new DateTimeImmutable()
        );
    }

    public static function fromDBRow(array $row): self {
        return new self(
            $row['id'],
            $row['user_id'],
            $row['homework_id'],
            $row['github_file_link'],
            new DateTimeImmutable($row['created_at'])
        );
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'homework_id' => $this->homeworkId,
            'github_file_link' => $this->githubFileLink,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
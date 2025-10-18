<?php

namespace Domain\Entities;

use DateTimeImmutable;
use JsonSerializable;


class Question implements JsonSerializable
{
    public string $id;
    public string $jobId;
    public string $text;
    public DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $jobId,
        string $text,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->jobId = $jobId;
        $this->text = $text;
        $this->createdAt = $createdAt;
    }

    public static function createNew(
        string $jobId,
        string $text
    ): self {
        return new self(
            id: uniqid('qst_', true),
            jobId: $jobId,
            text: $text,
            createdAt: new DateTimeImmutable()
        );
    }


    public static function fromDBRow(array $row): self
    {
        return new self(
            id: $row['id'],
            jobId: $row['job_id'],
            text: $row['text'],
            createdAt: new DateTimeImmutable($row['created_at'])
        );
    }




    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->jobId,
            'text' => $this->text,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
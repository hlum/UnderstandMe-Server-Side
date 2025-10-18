<?php

namespace Domain\Entities;
use JsonSerializable;
use DateTimeImmutable;




class Job implements JsonSerializable
{
    public string $id;
    public string $projectId;
    public Status $status;
    public ?DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        string $projectId,
        Status $status,
        ?DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function createNew(
        string $projectId,
        Status $status
    ): self {
        return new self(
            bin2hex(random_bytes(16)),
            $projectId,
            $status,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['project_id'],
            Status::from($row['status']),
            isset($row['created_at']) ? new DateTimeImmutable($row['created_at']) : null,
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'status' => $this->status instanceof Status ? $this->status->getValue() : $this->status,
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
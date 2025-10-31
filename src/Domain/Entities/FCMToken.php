<?php

namespace Domain\Entities;

use DateTimeImmutable;
;

class FCMToken
{
    public string $id;
    public string $userId;
    public string $deviceId;
    public string $deviceType;
    public ?string $fcmToken;
    public DateTimeImmutable $createdAt;

    private function __construct(
        string $id,
        string $userId,
        string $deviceId,
        string $deviceType,
        ?string $fcmToken,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->deviceId = $deviceId;
        $this->deviceType = $deviceType;
        $this->fcmToken = $fcmToken;
        $this->createdAt = $createdAt;
    }

    public static function createNew(
        string $userId,
        string $deviceId,
        string $deviceType,
        ?string $fcmToken
    ): self {
        return new self(
            bin2hex(random_bytes(16)),
            $userId,
            $deviceId,
            $deviceType,
            $fcmToken,
            new DateTimeImmutable()
        );
    }

    public static function fromDBRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['user_id'],
            $row['device_id'],
            $row['device_type'],
            $row['fcm_token'],
            new DateTimeImmutable($row['created_at'])
        );
    }
}
<?php
namespace Domain\Repositories;
use Domain\Entities\FCMToken;


interface FCMTokenRepositoryInterface
{
    public function insertFCMToken(FCMToken $fcmToken): void;
    public function updateFCMToken(string $userID, string $deviceID, string $newFCMToken): void;
    public function findByUserIdAndDeviceId(string $userId, string $deviceId): ?FCMToken;
    public function findByUserId(string $userId): array;
    public function deleteFCMToken(string $userId, string $deviceId): void;
}


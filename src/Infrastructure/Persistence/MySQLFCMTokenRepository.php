<?php

namespace Infrastructure\Persistence;
use Domain\Repositories\FCMTokenRepositoryInterface;
use Domain\Entities\FCMToken;
use mysqli;
use mysqli_result;


class MySQLFCMTokenRepository implements FCMTokenRepositoryInterface
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }


    public function insertFCMToken(FCMToken $fcmToken): void
    {
        $query = "INSERT INTO fcm_tokens (user_id, device_id, device_type, fcm_token) VALUES (?, ?, ?, ?)";
        $types = "ssss";
        $params = [
            $fcmToken->userId,
            $fcmToken->deviceId,
            $fcmToken->deviceType,
            $fcmToken->fcmToken
        ];
        $error_message = "FCMトークンの挿入に失敗しました。";

        $this->executeQuery($query, $types, $params, $error_message);
    }



    public function updateFCMToken(string $userID, string $deviceID, string $newFCMToken): void
    {
        $query = "UPDATE fcm_tokens SET fcm_token = ? WHERE user_id = ? AND device_id = ?";
        $types = "sss";
        $params = [$newFCMToken, $deviceID, $userID];
        $error_message = "FCMトークンの更新に失敗しました。";

        $this->executeQuery($query, $types, $params, $error_message);
    }




    public function findByUserIdAndDeviceId(string $userId, string $deviceId): ?FCMToken
    {
        $query = "SELECT * FROM fcm_tokens WHERE user_id = ? AND device_id = ?";
        $types = "ss";
        $params = [$userId, $deviceId];
        $error_message = "ユーザーIDとデバイスIDによるFCMトークンの検索に失敗しました。";

        $result = $this->executeQuery($query, $types, $params, $error_message);
        $row = $result->fetch_assoc();

        if ($row === null) {
            return null;
        }

        return FCMToken::fromDBRow($row);
    }



    private function executeQuery(
        string $query,
        string $types,
        array $params,
        string $error_message
    ): mysqli_result|bool {
        $stmt = $this->connection->prepare($query);

        if ($stmt === false) {
            throw new \RuntimeException('ステートメントの準備に失敗しました。詳細: ' . $this->connection->error);
        }

        $stmt->bind_param($types, ...$params);
        if ($stmt === false) {
            throw new \RuntimeException('パラメータのバインドに失敗しました。詳細: ' . $this->connection->error);
        }

        if (!$stmt->execute()) {
            throw new \RuntimeException('クエリの実行に失敗しました。詳細: ' . $stmt->error);
        }

        return $stmt->get_result();
    }
}
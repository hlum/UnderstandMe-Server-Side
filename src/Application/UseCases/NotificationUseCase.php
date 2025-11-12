<?php

namespace Application\UseCases;

use Domain\Entities\FCMToken;
use Helpers\NotificationHandler;

class NotificationUseCase
{
    private NotificationHandler $notificationHandler;
    private FCMTokenUseCase $fCMTokenUseCase;

    public function __construct(NotificationHandler $notificationHandler, FCMTokenUseCase $fCMTokenUseCase)
    {
        $this->notificationHandler = $notificationHandler;
        $this->fCMTokenUseCase = $fCMTokenUseCase;
    }

    /**
     * @return FCMToken[]
     */
    public function sendNotification(string $userID, string $title, string $body, string $homeworkID): array
    {
        // 無効なトークンを格納する配列
        $invalidTokens = [];

        $fcmTokens = $this->fCMTokenUseCase->getTokensByUserId($userID);

        foreach ($fcmTokens as $token) {

            try {
                $response = $this->notificationHandler->sendFCMNotification($token->fcmToken, $title, $body, $homeworkID);
                if (!$response) {
                    $invalidTokens[] = $token;
                }

                $responseCode = $response['code'] ?? null;
                if (in_array($responseCode, [400, 404, 410], true)) {
                    $invalidTokens[] = $token;
                }
            } catch (\Exception $e) {
                $invalidTokens = $fcmTokens;
            }
        }

        // 無効なトークンを削除
        $this->deleteInvalidTokens($invalidTokens);

        return $invalidTokens;
    }


    /**
     * 無効なトークンを削除する
     *
     * @param FCMToken[] $tokens
     * @return void
     */
    private function deleteInvalidTokens(array $tokens = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $failed = [];

        foreach ($tokens as $token) {
            try {
                $this->fCMTokenUseCase->deleteFCMToken($token->userId, $token->deviceId);
            } catch (\Throwable $e) {
                $failed[] = [
                    'userId' => $token->userId,
                    'deviceId' => $token->deviceId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (!empty($failed)) {
            // Log or handle the failed ones
            error_log('Some FCM tokens failed to delete: ' . json_encode($failed));
        }
    }
}
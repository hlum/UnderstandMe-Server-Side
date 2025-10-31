<?php

namespace Application\UseCases;
use Domain\Entities\FCMToken;
use Domain\Repositories\FCMTokenRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;


class FCMTokenUseCase
{
    private FCMTokenRepositoryInterface $fcmTokenRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        FCMTokenRepositoryInterface $fcmTokenRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->fcmTokenRepository = $fcmTokenRepository;
        $this->userRepository = $userRepository;
    }


    public function registerOrUpdateFCMToken(
        string $userID,
        string $deviceID,
        string $deviceType,
        string $fcmToken
    ) {

        // Userの存在をチェック
        $userInDB = $this->userRepository->findById($userID);

        if (!$userInDB) {
            throw new \Exception("指定されたユーザーは存在しません : " . $userID);
        }

        $fcmTokenInDB = $this->fcmTokenRepository->findByUserIdAndDeviceId($userID, $deviceID);

        if ($fcmTokenInDB) {
            // 既存のFCMトークンを更新
            $fcmTokenInDB->fcmToken = $fcmToken;
            $this->fcmTokenRepository->updateFCMToken($userID, $deviceID, $fcmToken);
            return;
        }

        // 新しいFCMトークンを作成して保存
        $newFCMToken = FCMToken::createNew(
            $userID,
            $deviceID,
            $deviceType,
            $fcmToken
        );
        $this->fcmTokenRepository->insertFCMToken($newFCMToken);
    }


    public function getTokensByUserId(string $userID): array
    {
        return $this->fcmTokenRepository->findByUserId($userID);
    }


}
<?php

namespace Application\UseCases;
use Domain\Entities\FCMToken;
use Domain\Repositories\FCMTokenRepositoryInterface;
use Domain\Repositories\UserRepositoryInterface;
use Exception;
use InvalidArgumentException;


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
        $userExist = $this->userExists($userID);
        if (!$userExist) {
            throw new Exception("Userが存在しません。" . $userID);
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


    /**
     * @return FCMToken[]
     */
    public function getTokensByUserId(string $userID): array
    {
        return $this->fcmTokenRepository->findByUserId($userID);
    }


    public function deleteFCMToken(string $userID, string $deviceID): void
    {
        // Userの存在をチェック
        if (!$this->userExists($userID)) {
            throw new InvalidArgumentException("Userが存在しません。" . $userID);
        }
        // FCMトークンの存在をチェック
        if (!$this->fcmTokenExists($userID, $deviceID)) {
            throw new InvalidArgumentException("FCMトークンが存在しません。" . $userID . ", " . $deviceID);
        }

        $this->fcmTokenRepository->deleteFCMToken($userID, $deviceID);
    }


    private function userExists(string $userID): bool
    {
        $user = $this->userRepository->findById($userID);
        return $user !== null;
    }


    private function fcmTokenExists(string $userID, string $deviceID): bool
    {
        $fcmToken = $this->fcmTokenRepository->findByUserIdAndDeviceId($userID, $deviceID);
        return $fcmToken !== null;
    }


}
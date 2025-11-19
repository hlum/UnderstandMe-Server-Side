<?php

namespace Application\UseCases;

use Domain\Entities\FCMToken;
use Domain\Entities\Homework;
use Domain\Entities\Job;
use Helpers\NotificationHandler;

class NotificationUseCase
{
    private NotificationHandler $notificationHandler;
    private FCMTokenUseCase $fCMTokenUseCase;
    private ?ClassUseCase $classUseCase;
    private ?UserUseCase $userUseCase;
    private ?HomeworkUseCase $homeworkUseCase;
    private ?ProjectUseCase $projectUseCase;

    public function __construct(
        NotificationHandler $notificationHandler, 
        FCMTokenUseCase $fCMTokenUseCase,
        ?ClassUseCase $classUseCase = null,
        ?UserUseCase $userUseCase = null,
        ?HomeworkUseCase $homeworkUseCase = null,
        ?ProjectUseCase $projectUseCase = null
    )
    {
        $this->notificationHandler = $notificationHandler;
        $this->fCMTokenUseCase = $fCMTokenUseCase;
        $this->classUseCase = $classUseCase;
        $this->userUseCase = $userUseCase;
        $this->homeworkUseCase = $homeworkUseCase;
        $this->projectUseCase = $projectUseCase;
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
     * クラスIDから対象ユーザーを取得する共通ロジック
     * 普通クラス: majorCode と admissionYear で全学生を取得
     * 選択科目: student_class_enrollments から登録学生のみ取得
     *
     * @param string $classID
     * @return array ユーザーの配列
     * @throws \Exception
     */
    private function getUsersForClass(string $classID): array
    {
        if ($this->classUseCase === null || $this->userUseCase === null) {
            throw new \Exception('ClassUseCase と UserUseCase が必要です');
        }

        $class = $this->classUseCase->findById($classID);
        
        if ($class->classCode === null) {
            // 普通クラス: majorCode と admissionYear で対象ユーザーを取得
            return $this->userUseCase->findByMajorCodeAndAdmissionYear($class->majorCode, $class->admissionYear);
        } else {
            // 選択科目: enrollment 登録学生のみ取得
            $studentIDs = $this->classUseCase->getAllStudentIDsInExtensionClass($classID);
            return $this->userUseCase->findByIDs($studentIDs);
        }
    }

    /**
     * クラスIDだけで通知を送る
     *
     * @param string $classID クラスID
     * @param string $title 通知タイトル
     * @param string $body 通知本文
     * @param string|null $resourceID リソースID（homeworkIDなど）
     * @return FCMToken[] 無効なトークンの配列
     */
    public function notifyClass(string $classID, string $title, string $body, ?string $resourceID = null): array
    {
        $users = $this->getUsersForClass($classID);
        $invalidTokens = [];

        foreach ($users as $user) {
            $invalid = $this->sendNotification($user->id, $title, $body, $resourceID ?? '');
            $invalidTokens = array_merge($invalidTokens, $invalid);
        }

        return $invalidTokens;
    }

    /**
     * Homework 追加時の通知
     *
     * @param Homework $homework 追加された宿題
     * @return FCMToken[] 無効なトークンの配列
     */
    public function notifyHomeworkAdded(Homework $homework): array
    {
        if ($this->classUseCase === null) {
            throw new \Exception('ClassUseCase が必要です');
        }

        $class = $this->classUseCase->findById($homework->classID);
        $title = "新しい宿題が追加されました";
        $body = $class->name . "に" . $homework->title . "が追加されました。";

        return $this->notifyClass($homework->classID, $title, $body, $homework->id);
    }

    /**
     * Job（問題生成）の成功/失敗通知
     *
     * @param Job $job 完了したJob
     * @param bool $success 成功かどうか
     * @return FCMToken[] 無効なトークンの配列
     */
    public function notifyJobResult(Job $job, bool $success): array
    {
        if ($this->projectUseCase === null || $this->homeworkUseCase === null) {
            throw new \Exception('ProjectUseCase と HomeworkUseCase が必要です');
        }

        // Job → Project → Homework → Class → Users の階層で対象を取得
        $project = $this->projectUseCase->findById($job->projectID);
        $homework = $this->homeworkUseCase->findById($project->homeworkID);

        $title = $success ? "問題生成完了のお知らせ" : "問題生成失敗のお知らせ";
        $body = $success 
            ? "あなたのプロジェクトの問題生成が完了しました。"
            : "あなたのプロジェクトの問題生成が失敗しました。再度お試しください。";

        return $this->notifyClass($homework->classID, $title, $body, $homework->id);
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
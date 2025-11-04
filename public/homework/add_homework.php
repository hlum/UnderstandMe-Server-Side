<?php

use Application\UseCases\ClassUseCase;
use Application\UseCases\UserUseCase;
use Application\UseCases\FCMTokenUseCase;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\HomeworkUseCase;
use Domain\Entities\Homework;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLFCMTokenRepository;
use Helpers\NotificationHandler;

ini_set('display_errors', 0);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST'])) {
    Response::send('error', 'Method not allowed. Use POST', 405);
}

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::checkTeacherKey($clientApiKey);

// Expected JSON structure
// {
// teacher_id: String,
// class_id: String nullable,
// title: String,
// description: String nullable,
// due_date: String nullable // ISO 8601 date format "2025-10-01T23:59:00Z"
// }

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$teacher_id = $input['teacher_id'] ?? null;
$class_id = $input['class_id'] ?? null;
$title = $input['title'] ?? null;
$description = $input['description'] ?? null;
$due_date = $input['due_date'] ?? null;

if (!isset($teacher_id)) {
    Response::send('error', '教師IDは必須です。', 400);
}
if ($teacher_id == null || !is_string($teacher_id)) {
    Response::send('error', '無効な教師ID形式です。', 400);
}
if (!isset($title)) {
    Response::send('error', 'タイトルは必須です。', 400);
}
if ($title == null || !is_string($title)) {
    Response::send('error', '無効なタイトル形式です。', 400);
}

try {
    $due_date = $due_date ? new DateTimeImmutable($due_date) : null;
} catch (Exception $e) {
    Response::send('error', '無効な締め切り日形式です。', 400);
}

if ($due_date == null || !($due_date instanceof DateTimeImmutable)) {
    Response::send('error', '無効な締め切り日形式です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $classRepository = new MySQLClassRepository($connection);
    $fcmTokenRepository = new MySQLFCMTokenRepository($connection);

    $classUseCase = new ClassUseCase($classRepository, $userRepository);
    $userUseCase = new UserUseCase($userRepository);
    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);
    $fcmTokenUseCase = new FCMTokenUseCase($fcmTokenRepository, $userRepository);

    // NotificationHandlerの初期化
    $notificationHandler = new NotificationHandler(FIREBASE_PROJECT_ID, FIREBASE_SERVICE_ACCOUNT_PATH);


    $newHomework = Homework::createNew(
        $teacher_id,
        $class_id,
        $title,
        $description,
        $due_date
    );

    // 宿題を追加
    $homeworkUseCase->add($newHomework);

    // クラス情報を取得
    $classToNotify = $classUseCase->findById($class_id);

    // 通知対象のユーザーを取得
    $usersToNotify = $userUseCase->findByMajorCodeAndAdmissionYear($classToNotify->majorCode, $classToNotify->admissionYear);

    $errorsSendingNotifications = [];
    // 対象のユーザーに通知を送信
    foreach ($usersToNotify as $user) {
        // ユーザーの全てのFCMトークンを取得
        $fcmTokens = $fcmTokenUseCase->getTokensByUserId($user->id);
        
        foreach ($fcmTokens as $fcmTokenEntity) {
            if ($fcmTokenEntity->fcmToken) {
                $response = $notificationHandler->sendFCMNotification(
                    $fcmTokenEntity->fcmToken,
                    '新しい宿題が追加されました',
                    "{$classToNotify->name}に{$newHomework->title}の宿題が追加されました。",
                    $newHomework->id
                );
                if ($response['code'] !== 200) {
                    $errorsSendingNotifications[] = "学生番号: {$user->studentCode}, デバイスID: {$fcmTokenEntity->deviceId}, レスポンス: {$response['response']}";

                    // 無効なトークンの場合は削除
                    if ($response['code'] === 410 || $response['code'] === 404 || $response['code'] === 400) {
                        try {
                            $fcmTokenUseCase->deleteFCMToken($user->id, $fcmTokenEntity->deviceId);
                        } catch (Throwable $e) {
                            $errorsSendingNotifications[] = "無効なFcmTokenの削除に失敗しました。学生番号: {$user->studentCode}, デバイスID: {$fcmTokenEntity->deviceId}, エラー: {$e->getMessage()}";
                        }
                    }
                }
            }
        }
    }

    if (!empty($errorsSendingNotifications)) {
        // エラーログを出力
        Response::send('error', '宿題の追加が成功しましたが、一部の通知の送信に失敗しました。', 200, json_encode($errorsSendingNotifications));
    } else {
        Response::send('success', '宿題の追加が成功しました。', 200, json_encode($usersToNotify));
    }

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
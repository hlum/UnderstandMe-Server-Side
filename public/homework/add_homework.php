<?php

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\ClassUseCase;
use Application\UseCases\NotificationUseCase;
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
use Infrastructure\Persistence\MySQLStudentClassEnrollmentRepository;

try {


    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST'])) {
        Response::send('fail', 'Method not allowed. Use POST', 405, null, 'validation_error');
    }

    // API Key validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    $teacherID = ApiKeyValidator::checkTeacherKey($clientApiKey);

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
        throw new ValidationException('無効なJSONデータです。');
    }

    $passedTeacherID = $input['teacher_id'] ?? null;
    $class_id = $input['class_id'] ?? null;
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;
    $due_date = $input['due_date'] ?? null;

    if(!isset($class_id)) {
        throw new ValidationException('class_idは必須です。');
    }

    if (!isset($passedTeacherID)) {
        throw new ValidationException('teacher_idは必須です。');
    }

    if ($passedTeacherID == null || !is_string($passedTeacherID)) {
        throw new ValidationException('無効なteacher_id形式です。');
    }
    if($passedTeacherID !== $teacherID) {
        throw new ValidationException('トークンの教師IDと渡された教師IDが一致しません。他のユーザーの情報を操作することはできません。');
    }

    if (!isset($title)) {
        throw new ValidationException('titleは必須です。');
    }
    if ($title == null || !is_string($title)) {
        throw new ValidationException('無効なtitle形式です。');
    }

    try {
        $due_date = $due_date ? new DateTimeImmutable($due_date) : null;
    } catch (Exception $e) {
        throw new ValidationException('無効な締め切り日形式です。');
    }

    if ($due_date == null || !($due_date instanceof DateTimeImmutable)) {
        throw new ValidationException('無効な締め切り日形式です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
    
    // 教師ユーザーの検証
    $userUseCase->verifyTeacher($teacherID);

    $classRepository = new MySQLClassRepository($connection);
    $fcmTokenRepository = new MySQLFCMTokenRepository($connection);
    $studentClassEnrollmentRepo = new MySQLStudentClassEnrollmentRepository($connection);

    $classUseCase = new ClassUseCase($classRepository, $userRepository, $studentClassEnrollmentRepo);
    $userUseCase = new UserUseCase($userRepository);
    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);
    $fcmTokenUseCase = new FCMTokenUseCase($fcmTokenRepository, $userRepository);
    
    // NotificationHandlerの初期化
    $notificationHandler = new NotificationHandler(FIREBASE_PROJECT_ID, FIREBASE_SERVICE_ACCOUNT_PATH);
    $notificationUseCase = new NotificationUseCase($notificationHandler, $fcmTokenUseCase, $classUseCase, $userUseCase, $homeworkUseCase, null);


    $newHomework = Homework::createNew(
        $passedTeacherID,
        $class_id,
        $title,
        $description,
        $due_date
    );

    // 宿題を追加
    $homeworkUseCase->add($newHomework);

    // 通知を送信（NotificationUseCaseで一元管理）
    $invalidTokens = $notificationUseCase->notifyHomeworkAdded($newHomework);

    if (!empty($invalidTokens)) {
        Response::send('success', '課題は正常に追加されましたが、一部の学生への通知は失敗しました。', 200, json_encode($invalidTokens));
    }
    
    Response::send('success', '宿題の追加が成功しました。', 200);
    

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
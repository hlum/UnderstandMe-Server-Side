<?php

use Application\UseCases\UserUseCase;
// 教師側のみ利用するAPIエンドポイント
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\HomeworkUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLClassRepository;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
        Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
    }

    // API KEY Validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    $userID = ApiKeyValidator::check($clientApiKey);


    // Possible queries
    /*
    by homework_id
    by class_id (get all homeworks for a specific class)
    by teacher_id (get all homeworks assigned by a specific teacher)
    */

    $homework_id = $_GET['id'] ?? null;
    $class_id = $_GET['class_id'] ?? null;
    $passedTeacherUserID = $_GET['teacher_id'] ?? null;


    if($passedTeacherUserID !== null && $userID != $passedTeacherUserID) {
        throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
    }


    
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
    
    // 教師かどうか確認
    $userUseCase->verifyTeacher($userID);


    $classRepository = new MySQLClassRepository($connection);

    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);

} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}

try {
    $homeworks = [];

    if (isset($homework_id)) {
        $homework = $homeworkUseCase->findById($homework_id);
        if ($homework !== null) {
            $homeworks = [$homework];
        }
    } elseif (isset($class_id)) {
        $homeworks = $homeworkUseCase->findByClassId($class_id);
    } elseif (isset($passedTeacherUserID)) {
        $homeworks = $homeworkUseCase->findByTeacherId($passedTeacherUserID);
    } else {
        throw new ValidationException('少なくとも1つのクエリパラメータを指定する必要があります。');
    }

    Response::send('success', '宿題の取得に成功しました', 200, $homeworks);

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
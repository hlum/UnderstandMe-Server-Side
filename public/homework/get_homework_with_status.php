<?php

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


$homework_id = $_GET['id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;


try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
        Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
    }


    // API KEY Validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::check($clientApiKey);

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $classRepository = new MySQLClassRepository($connection);

    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);

} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


try {
    $homeworksWithStatus = [];
    if (isset($homework_id)) {
        $homeworksWithStatus = $homeworkUseCase->findByIDWithStatus($homework_id, $student_id);
    } else if (isset($student_id) && isset($class_id)) {
        $homeworksWithStatus = $homeworkUseCase->findByClassIDWithStatus($class_id, $student_id);
    } else if (isset($student_id)) {
        $homeworksWithStatus = $homeworkUseCase->findByStudentIDWithStatus($student_id);
    } else {
        throw new ValidationException('idかstudent_idとclass_idを指定してください');
    }

    Response::send('success', "課題の取得に成功しました", 200, $homeworksWithStatus);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
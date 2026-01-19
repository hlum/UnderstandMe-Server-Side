<?php
require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLStudentClassEnrollmentRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Application\UseCases\StudentClassEnrollmentUseCase;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', '1');
error_reporting(E_ALL);



try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::send('fail', 'Method not allowed. Use POST', 405, null, 'validation_error');
    }

    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::check($clientApiKey);

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }


    $studentID = $input['student_id'] ?? null;
    $classCode = $input['class_code'] ?? null;


    if (empty($studentID)) {
        throw new ValidationException('student_idは必須です。');
    }

    if (empty($classCode)) {
        throw new ValidationException('class_codeは必須です。');
    }


    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepo = new MySQLUserRepository($connection);
    $classRepo = new MySQLClassRepository($connection);
    $studentClassEnrollmentRepo = new MySQLStudentClassEnrollmentRepository($connection);

    $studentClassEnrollmentUseCase = new StudentClassEnrollmentUseCase($studentClassEnrollmentRepo, $userRepo, $classRepo);
    $studentClassEnrollmentUseCase->enrollStudent($studentID, $classCode);

    Response::send('success', '学生をクラスに正常に登録しました。');
} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (\Exception $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
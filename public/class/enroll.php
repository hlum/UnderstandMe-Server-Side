<?php
require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLStudentClassEnrollmentRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Src\Application\UseCases\StudentClassEnrollmentUseCase;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', '1');
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::send('error', 'Method not allowed. Use POST', 405);
}

$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}


$studentID = $input['student_id'] ?? null;
$classCode = $input['class_code'] ?? null;


if (!$studentID) {
    Response::send('error', '学生IDは必須です。', 400);
}

if (!$classCode) {
    Response::send('error', 'クラスIDは必須です。', 400);
}



try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepo = new MySQLUserRepository($connection);
    $classRepo = new MySQLClassRepository($connection);
    $studentClassEnrollmentRepo = new MySQLStudentClassEnrollmentRepository($connection);

    $studentClassEnrollmentUseCase = new StudentClassEnrollmentUseCase($studentClassEnrollmentRepo, $userRepo, $classRepo);
    $studentClassEnrollmentUseCase->enrollStudent($studentID, $classCode);
} catch (AppException $e) {
    Response::send('error', $e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    Response::send('error', '予期しないエラーが発生しました。', 500);
}
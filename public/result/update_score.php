<?php

use Application\CustomExceptions\ValidationException;
use Application\UseCases\ResultUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLResultRepository;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../vendor/autoload.php';


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {

    if (!in_array($_SERVER['REQUEST_METHOD'], ['PATCH'])) {
        Response::send('fail', 'Method not allowed. Use PATCH', 405, null, 'validation_error');
    }

    $headers = getallheaders();
    $clientAPIKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::checkTeacherKey($clientAPIKey);



    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    $newScore = $input['new_score'] ?? null;
    $homeworkID = $input['homework_id'] ?? null;
    $studentID = $input['student_id'] ?? null;

    if (!isset($newScore) || !isset($homeworkID) || !isset($studentID)) {
        throw new ValidationException('new_score, homework_id, と student_id は必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $resultRepository = new MySQLResultRepository($connection);
    $resultUseCase = new ResultUseCase($resultRepository);

    $resultUseCase->updateScore($homeworkID, $studentID, $newScore);
    Response::send('success', 'スコアが正常に更新されました。', 200);
} catch (ValidationException $ve) {
    Response::send('fail', $ve->getMessage(), 400, null, 'validation_error');
} catch (Exception $e) {
    Response::send('error', 'サーバーエラーが発生しました。', 500, null, 'server_error');
}
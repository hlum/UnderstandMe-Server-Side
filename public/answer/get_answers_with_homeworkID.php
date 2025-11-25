<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLAnswerRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;
use Application\UseCases\AnswerUseCase;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
}


// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


$homeworkID = $_GET['homework_id'] ?? null;
$userID = $_GET['user_id'] ?? null;




try {
    if (!isset($homeworkID) || !isset($userID)) {
        throw new ValidationException('homework_id と user_id は必須です。');
    }
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $answerRepository = new MySQLAnswerRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $questionRepository = new MySQLQuestionRepository($connection);

    $answerUseCase = new AnswerUseCase(
        $answerRepository,
        $userRepository,
        $questionRepository
    );

    $answers = $answerUseCase->findAnswersForHomework($homeworkID, $userID);

    Response::send('success', '回答の取得成功', 200, json_encode($answers));
} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());

} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
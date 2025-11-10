<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use App\Application\CustomExceptions\AppException;
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
    Response::send('error', 'Method not allowed. Use GET', 405);
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


$homeworkID = $_GET['homework_id'] ?? null;
$userID = $_GET['user_id'] ?? null;


if (!isset($homeworkID) || !isset($userID)) {
    Response::send('error', 'homework_id と user_id は必須です。', 400);
}


try {
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
    Response::send('error', $e->getMessage(), $e->getStatusCode());

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
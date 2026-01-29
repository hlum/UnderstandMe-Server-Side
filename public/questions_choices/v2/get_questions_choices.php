<?php

use Application\UseCases\UserUseCase;
use Infrastructure\Persistence\MySQLUserRepository;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLQuestionsAndChoicesRepository;
use Application\UseCases\QuestionsAndChoicesUseCase;


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


    $homeworkID = $_GET['homework_id'] ?? null;
    $passedUserID = $_GET['user_id'] ?? null;

    if (!isset($homeworkID) || !isset($passedUserID)) {
        throw new ValidationException('homework_id と user_id は必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Authorization: check if the user is teacher or student. If student, check if the student is requesting their own data.
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
    $isTeacher = $userUseCase->isTeacher($userID);

    if( !$isTeacher ) {
        if($userID != $passedUserID) {
            throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
        }
    }

    $questionsAndChoicesRepository = new MySQLQuestionsAndChoicesRepository($connection);
    $questionAndChoicesUseCase = new QuestionsAndChoicesUseCase($questionsAndChoicesRepository);
    $questionsAndChoices = $questionAndChoicesUseCase->getQuestionsAndChoicesByHomeworkIdWithNoCorrectChoiceData($homeworkID, $passedUserID);

    Response::send('success', '質問と選択肢の取得成功', 200, $questionsAndChoices);
} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


<?php
// 正解の選択肢が含まれている質問と選択肢を取得するAPI
// 教師以外はアクセスできません。

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLQuestionsAndChoicesRepository;
use Application\UseCases\QuestionsAndChoicesUseCase;
use Application\UseCases\UserUseCase;
use Infrastructure\Persistence\MySQLUserRepository;

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
    $student_user_id = $_GET['user_id'] ?? null;

    if (!isset($homeworkID) || !isset($student_user_id)) {
        throw new ValidationException('homework_id と user_id は必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);


    // 教師か検証
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
    $userUseCase->verifyTeacher($userID);

    $questionsAndChoicesRepository = new MySQLQuestionsAndChoicesRepository($connection);
    $questionAndChoicesUseCase = new QuestionsAndChoicesUseCase($questionsAndChoicesRepository);
    $questionsAndChoices = $questionAndChoicesUseCase->getQuestionsAndChoicesByHomeworkId($homeworkID, $student_user_id);

    Response::send('success', '質問と選択肢の取得成功', 200, $questionsAndChoices);
} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


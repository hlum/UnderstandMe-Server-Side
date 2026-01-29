<?php

use Application\UseCases\ResultUseCase;
use Infrastructure\Persistence\MySQLResultRepository;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\ChoiceUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;

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


    $questionID = $_GET['question_id'] ?? null;
    $homeworkID = $_GET['homework_id'] ?? null;
    $passedUserID = $_GET['user_id'] ?? null;


    if ($passedUserID !== $userID) {
        throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
    }

    if (!isset($questionID)) {
        throw new ValidationException('question_id は必須です。');
    }

    if(!isset($homeworkID)) {
        throw new ValidationException('homework_id は必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $choiceRepository = new MySQLChoiceRepository($connection);
    $questionRepository = new MySQLQuestionRepository($connection);
    $choiceUseCase = new ChoiceUseCase(choiceRepository: $choiceRepository, questionRepository: $questionRepository);

    // the question has to be answered before getting the correct choice
    // VALIDATION( check whether the result exists for the homeworkID and the user id)
    $resultRepository = new MySQLResultRepository($connection);
    $resultUseCase = new ResultUseCase($resultRepository);  
    $resultInDB = $resultUseCase->fetchResultWithHomeworkIDAndUserID($homeworkID, $userID);
    if (!$resultInDB) {
        throw new ValidationException('回答済みの問題ではないため、正しい選択肢を取得できません。');
    }
    
    $choices[] = $choiceUseCase->fetchCorrectChoiceByQuestionId($questionID);

    Response::send('success', '正解の回答取得成功', 200, $choices);
} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


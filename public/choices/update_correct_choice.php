<?php

use Application\CustomExceptions\ValidationException;
use Application\UseCases\ChoiceUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../vendor/autoload.php';



try {

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], ['PATCH'])) {
        Response::send('fail', 'Method not allowed. Use PATCH', 405, null, 'validation_error');
    }


    $headers = getallheaders();
    $clientAPIKEY = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::checkTeacherKey($clientAPIKEY);

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    $newCorrectChoiceID = $input['new_correct_choice_id'] ?? null;
    $questionID = $input['question_id'] ?? null;


    if (!isset($newCorrectChoiceID) || !isset($questionID)) {
        throw new ValidationException('new_correct_choice_id と question_id は必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // データの一貫性を保つためにトランザクションを開始
    $connection->begin_transaction();

    $choiceRepository = new MySQLChoiceRepository($connection);
    $questionRepository = new MySQLQuestionRepository($connection);
    $choiceUseCase = new ChoiceUseCase($choiceRepository, $questionRepository);
    $choiceUseCase->updateCorrectChoice($newCorrectChoiceID, $questionID);

    // すべて成功した場合、トランザクションをコミット
    $connection->commit();
    Response::send('success', '正しい選択肢が正常に更新されました。', 200);
} catch (ValidationException $ve) {
    if (isset($connection)){
        $connection->rollback();
    }
    Response::send('fail', $ve->getMessage(), 400, null, 'validation_error');
} catch (Exception $e) {
    if (isset($connection)){
        $connection->rollback();
    }
    Response::send('error', 'サーバーエラーが発生しました。', 500, null, 'server_error');
} finally {
    if (isset($connection)){
        $connection->close();
    }
}
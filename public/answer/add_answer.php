<?php
require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\AnswerUseCase;
use Application\UseCases\ChoiceUseCase;
use Application\UseCases\ResultUseCase;
use Infrastructure\Persistence\MySQLAnswerRepository;
use Infrastructure\Persistence\MySQLChoiceRepository;
use Infrastructure\Persistence\MySQLQuestionRepository;
use Infrastructure\Persistence\MySQLResultRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Domain\Entities\Answer;
use Domain\Entities\Result;
use Helpers\Response;
use Helpers\ApiKeyValidator;



header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}



try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::send('fail', 'Method not allowed. Use POST', 405, null, 'validation_error');
    }

    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    $userID = ApiKeyValidator::check($clientApiKey);

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    $questionID = $input['question_id'] ?? null;
    $homeworkID = $input['homework_id'] ?? null;
    $passedUserID = $input['user_id'] ?? null;
    $selectedChoiceID = $input['selected_choice_id'] ?? null;
    $totalQuestions = $input['total_questions'] ?? null;

    if ($passedUserID !== $userID) {
        throw new ValidationException('トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。');
    }

    if (!$questionID || !$passedUserID || !$homeworkID || !$totalQuestions) {
        throw new ValidationException('必要なフィールドが不足しています。');
    }
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    // データの一貫性を保つためにトランザクションを開始
    $connection->begin_transaction();

    $answerRepo = new MySQLAnswerRepository($connection);
    $userRepo = new MySQLUserRepository($connection);
    $questionRepo = new MySQLQuestionRepository($connection);
    $choiceRepo = new MySQLChoiceRepository($connection);
    $resultRepo = new MySQLResultRepository($connection);

    $answerUseCase = new AnswerUseCase($answerRepo, $userRepo, $questionRepo);
    $choiceUseCase = new ChoiceUseCase($choiceRepo, $questionRepo);
    $resultUseCase = new ResultUseCase($resultRepo);

    // --- Save answer ---
    if (isset($selectedChoiceID)) {
        $newAnswer = Answer::createNew($questionID, $userID, $selectedChoiceID);
        $answerUseCase->addAnswer($newAnswer);
    }


    // --- Check if correct ---
    $correctChoice = null;
    $choiceIsCorrect = false;
    if (isset($selectedChoiceID)) {
        $correctChoice = $choiceUseCase->fetchCorrectChoiceByQuestionId($questionID);
        $choiceIsCorrect = ($selectedChoiceID === $correctChoice->id);
    }

    // --- Update or create result ---
    $resultInDB = $resultUseCase->fetchResultWithHomeworkIDAndUserID($homeworkID, $userID);

    if (!$resultInDB) {
        $correctAnswers = $choiceIsCorrect ? 1 : 0;
        $score = (int) (($correctAnswers / $totalQuestions) * 100);
        $newResult = Result::createNew($userID, $homeworkID, (int) $totalQuestions, $correctAnswers, $score);
        $resultUseCase->saveNewResult($newResult);
    } else {
        $newCorrect = $resultInDB->correctAnswers + ($choiceIsCorrect ? 1 : 0);
        $newScore = ($newCorrect / $resultInDB->totalQuestions) * 100;
        $resultUseCase->updateResult($resultInDB->id, $newScore, $newCorrect);
    }

    // すべて成功した場合、トランザクションをコミット
    $connection->commit();
    $correctChoiceID = $correctChoice ? $correctChoice->id : null;
    Response::send('success', 'Answer and result updated successfully.', 200, [['correct_choice_id' => $correctChoiceID]]);

} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
    if (isset($connection)){
        $connection->rollback();
    }
} catch (Throwable $e) {
    if (isset($connection)){
        $connection->rollback();
    }
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
} finally {
    if (isset($connection)){
        $connection->close();
    }
}
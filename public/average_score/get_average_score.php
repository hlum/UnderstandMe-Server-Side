<?php
// 学生のIDで科目ごとの平均スコアを取得するAPI

use Application\UseCases\AverageScoreUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLAverageScoreRepository;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../vendor/autoload.php';



try {
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
    $userID = ApiKeyValidator::check($clientApiKey);


    $passedUserID = $_GET['student_id'] ?? null;
    if (!isset($passedUserID)) {
        Response::send('fail', 'student_id は必須です。', 400, null, 'validation_error');
    }
    
    if ($passedUserID !== $userID) {
        Response::send('fail', 'トークンのユーザーIDと渡されたユーザーIDが一致しません。他のユーザーの情報を操作することはできません。', 403, null, 'validation_error');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $averageScoreRepository = new MySQLAverageScoreRepository($connection);
    $averageScoreUseCase = new AverageScoreUseCase($averageScoreRepository);
    $averageScores = $averageScoreUseCase->fetch($passedUserID);

    Response::send('success', '平均スコアの取得成功', 200, $averageScores);
} catch (Exception $e) {
    Response::send('fail', 'サーバーエラーが発生しました。', 500, null, 'server_error');
}
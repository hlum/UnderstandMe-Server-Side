<?php

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\HomeworkUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;

require __DIR__ . '/../../vendor/autoload.php';


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");



try {

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }


    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        Response::send('fail', 'Method Not Allowed. Use DELETE', 405, null, 'validation_error');
    }

    // API KEY Validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::checkTeacherKey($clientApiKey);


    $id = $_GET['id'] ?? null;


    if (!$id) {
        throw new ValidationException('idは必須です。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $classRepository = new MySQLClassRepository($connection);
    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);

    $homeworkUseCase->deleteByID($id);

    Response::send('success', '宿題が正常に削除されました。', 200);

} catch (AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
} finally {
    if(isset($connection)){
        $connection->close();
    }
}
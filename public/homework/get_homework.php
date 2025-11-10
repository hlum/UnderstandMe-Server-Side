<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../vendor/autoload.php';

use App\Application\CustomExceptions\AppException;
use Application\UseCases\HomeworkUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLClassRepository;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('error', 'Method not allowed. Use GET', 405);
}

// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


// Possible queries
/*
by homework_id
by class_id (get all homeworks for a specific class)
by teacher_id (get all homeworks assigned by a specific teacher)
*/

$homework_id = $_GET['id'] ?? null;
$class_id = $_GET['class_id'] ?? null;
$teacher_id = $_GET['teacher_id'] ?? null;

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $classRepository = new MySQLClassRepository($connection);

    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}

try {
    $homeworks = [];

    if (isset($homework_id)) {
        $homework = $homeworkUseCase->findById($homework_id);
        if ($homework !== null) {
            $homeworks = [$homework];
        }
    } elseif (isset($class_id)) {
        $homeworks = $homeworkUseCase->findByClassId($class_id);
    } elseif (isset($teacher_id)) {
        $homeworks = $homeworkUseCase->findByTeacherId($teacher_id);
    } else {
        Response::send('error', '少なくとも1つのクエリパラメータを指定する必要があります。', 400);
    }

    Response::send('success', '宿題の取得に成功しました', 200, json_encode($homeworks));

} catch(AppException $e) {
    Response::send('error', $e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
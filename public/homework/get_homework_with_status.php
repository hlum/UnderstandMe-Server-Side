<?php

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



// curl -X POST "http://24cm0138.main.jp/understand_me/public/homework/get_homework_with_status.php?id=8d82b12f53cd8a07db1a17f00950b6a1" \
//   -H "Content-Type: application/json"
//   -H "Authorization: 'afskjw42y8571wsdkls514amoiejojsdk'"


// Possible queries
/*
by id, student_id ( homework id , student_id)
by student_id and class_id (get homeworks for a student in a specific class)
*/

$homework_id = $_GET['id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;


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
    $homeworksWithStatus = [];
    if (isset($homework_id)) {
        $homeworksWithStatus = $homeworkUseCase->findByIDWithStatus($homework_id, $student_id);
    } else if (isset($student_id) && isset($class_id)) {
        $homeworksWithStatus = $homeworkUseCase->findByClassIDWithStatus($class_id, $student_id);
    } else if (isset($student_id)) {
        $homeworksWithStatus = $homeworkUseCase->findByStudentIDWithStatus($student_id);
    } else {
        Response::send('error', 'idかstudent_idとclass_idを指定してください', 400);
    }

    Response::send('success', "課題の取得に成功しました", 200, json_encode($homeworksWithStatus));
} catch(AppException $e) {
    Response::send('error', $e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
<?php

require __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\HomeworkUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLMajorRepository;


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if(!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('error', 'Method not allowed. Use GET', 405);
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


// Possible queries
/*
by homework_id
by student_id
by student_id and major_id (get homeworks for a student in a specific major)
by major_id (get all homeworks for a specific major)
by teacher_id (get all homeworks assigned by a specific teacher)
*/

$homework_id = $_GET['id'] ?? null;
$student_id   = $_GET['student_id']   ?? null;
$major_id    = $_GET['major_id']    ?? null;
$teacher_id  = $_GET['teacher_id']  ?? null;


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $majorRepository = new MySQLMajorRepository($connection);

    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $majorRepository);

} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}

try {
    $homeworks = [];

    if (isset($homework_id)) {
        $homework = $homeworkUseCase->findById($homework_id);
        if ($homework !== null) {
            $homeworks = [$homework];
        }
    } elseif (isset($student_id)) {
        $homeworks = $homeworkUseCase->findByStudentId($student_id);
    } elseif (isset($major_id)) {
        $homeworks = $homeworkUseCase->findByMajorId($major_id);
    } elseif (isset($teacher_id)) {
        $homeworks = $homeworkUseCase->findByTeacherId($teacher_id);
    } else {
        Response::send('error', '少なくとも1つのクエリパラメータを指定する必要があります。', 400);
    }

    Response::send('success', '宿題の取得に成功しました', 200, json_encode($homeworks));

} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}
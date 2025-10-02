<?php

require __DIR__ .'/../../vendor/autoload.php';
use Application\UseCases\HomeworkUseCase;
use Domain\Entities\Homework;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLMajorRepository;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if(!in_array($_SERVER['REQUEST_METHOD'], ['PATCH'])) {
    Response::send('error', 'Method not allowed. Use UPDATE', 405);
}

$headers = getallheaders();

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::checkTeacherKey($clientApiKey);

// Expected JSON structure
// {
    // teacher_id: String,
    // major_id: String nullable,
    // title: String,
    // description: String nullable,
    // due_date: String nullable // ISO 8601 date format "2025-10-01T23:59:00Z"
// }

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$teacher_id = $input['teacher_id'] ?? null;
$major_id = $input['major_id'] ?? null;
$title = $input['title'] ?? null;
$description = $input['description'] ?? null;
$due_date = $input['due_date'] ?? null;

if (!isset($teacher_id)) {
    Response::send('error', '教師IDは必須です。', 400);
}
if($teacher_id == null || !is_string($teacher_id)){
    Response::send('error', '無効な教師ID形式です。', 400);
}
if (!isset($title)) {
    Response::send('error', 'タイトルは必須です。', 400);
}
if($title == null || !is_string($title)){
    Response::send('error', '無効なタイトル形式です。', 400);
}

$due_date = new DateTimeImmutable($due_date);
if($due_date == null || !($due_date instanceof DateTimeImmutable)){
    Response::send('error', '無効な締め切り日形式です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $majorRepository = new MySQLMajorRepository($connection);

    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $majorRepository);
    $newHomework = Homework::createNew(
        $teacher_id,
        $major_id,
        $title,
        $description,
        $due_date
    );

    $homeworkUseCase->add($newHomework);
    Response::send('success', '宿題の追加が成功しました。', 200);

} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}
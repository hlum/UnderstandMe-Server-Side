<?php

require __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\MajorUseCase;
use Domain\Entities\Major;
use Helpers\Response;
use Helpers\ApiKeyValidator;
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
    // name: String,
    // admission_year: Integer,
    // class_name: String
// }

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$name = $input['name'] ?? null;
$admission_year = $input['admission_year'] ?? null;
$class_name = $input['class_name'] ?? null;

if (!isset($name)) {
    Response::send('error', '学科名は必須です。', 400);
}
if($name == null || !is_string($name)){
    Response::send('error', '無効な学科名形式です。', 400);
}
if (!isset($admission_year)) {
    Response::send('error', '入学年度は必須です。', 400);
}
if($admission_year == null || !is_int($admission_year)){
    Response::send('error', '無効な入学年度形式です。', 400);
}
if (!isset($class_name)) {
    Response::send('error', 'クラス名は必須です。', 400);
}
if($class_name == null || !is_string($class_name)){
    Response::send('error', '無効なクラス名形式です。', 400);
}

try {

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $majorRepository = new MySQLMajorRepository($connection);
    $majorUseCase = new MajorUseCase($majorRepository);
    $newMajor = Major::createNew(
        name: $name,
        admissionYear: $admission_year,
        className: $class_name
    );

    $majorUseCase->add($newMajor);
    Response::send('success', '学科が正常に追加されました。', 200);

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
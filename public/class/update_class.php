<?php
require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Application\UseCases\ClassUseCase;
use Domain\Entities\ClassEntity;
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLStudentClassEnrollmentRepository;
use Infrastructure\Persistence\MySQLUserRepository;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: UPDATE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");




if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
 if (!in_array($_SERVER['REQUEST_METHOD'], ['UPDATE'])) {
    Response::send('error', 'Method not allowed. Use UPDATE', 405);
}

$headers = getallheaders();
// API Key validation
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::checkTeacherKey($clientApiKey);

// Expected JSON structure
// {
// id: String,
// name: String,
// teacher_id: String,
// admission_year: Integer,
// major_code: String,
// class_code: String
// }


$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$id = $input['id'] ?? null;
$name = $input['name'] ?? null;
$admission_year = $input['admission_year'] ?? null;
$major_code = $input['major_code'] ?? null;
$teacher_id = $input['teacher_id'] ?? null;
$class_code = $input['class_code'] ?? null;

if (!isset($id)) {
    Response::send('error', 'クラスIDは必須です。', 400);
}

if (!isset($name)) {
    Response::send('error', '授業名は必須です。', 400);
}
if ($name == null || !is_string($name)) {
    Response::send('error', '無効な学科名形式です。', 400);
}

if (!isset($teacher_id)) {
    Response::send('error', '教師IDは必須です。', 400);
}

if ($teacher_id == null || !is_string($teacher_id)) {
    Response::send('error', '無効な教師ID形式です。', 400);
}

if (!isset($admission_year)) {
    Response::send('error', '入学年度は必須です。', 400);
}
if ($admission_year == null || !is_int($admission_year)) {
    Response::send('error', '無効な入学年度形式です。', 400);
}
if (!isset($major_code)) {
    Response::send('error', '専攻のコードは必須です。', 400);
}
if ($major_code == null || !is_string($major_code)) {
    Response::send('error', '無効な専攻のコード形式です。', 400);
}


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $classRepository = new MySQLClassRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $studentClassEnrollmentRepo = new MySQLStudentClassEnrollmentRepository($connection);
    $classUseCase = new ClassUseCase($classRepository, $userRepository, $studentClassEnrollmentRepo);

    $classToUpdate = ClassEntity::createNew(
        id: $id,
        teacher_id: $teacher_id,
        name: $name,
        admissionYear: $admission_year,
        majorCode: $major_code,
        classCode: $class_code
    );

    $classUseCase->updateClass($classToUpdate);
    Response::send('success', 'クラス情報が更新されました。', 200);
} catch(AppException $e) {
    Response::send('error', $e->getMessage(), $e->getStatusCode());
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
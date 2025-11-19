<?php
require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
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
    Response::send('fail', 'Method not allowed. Use UPDATE', 405, null, 'validation_error');
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
    throw new ValidationException('無効なJSONデータです。');
}

$id = $input['id'] ?? null;
$name = $input['name'] ?? null;
$admission_year = $input['admission_year'] ?? null;
$major_code = $input['major_code'] ?? null;
$teacher_id = $input['teacher_id'] ?? null;
$class_code = $input['class_code'] ?? null;

if (!isset($id)) {
    throw new ValidationException('クラスIDは必須です。');
}

if (!isset($name)) {
    throw new ValidationException('授業名は必須です。');
}
if ($name == null || !is_string($name)) {
    throw new ValidationException('無効な学科名形式です。');
}

if (!isset($teacher_id)) {
    throw new ValidationException('教師IDは必須です。');
}

if ($teacher_id == null || !is_string($teacher_id)) {
    throw new ValidationException('無効な教師ID形式です。');
}

if (!isset($admission_year)) {
    throw new ValidationException('入学年度は必須です。');
}
if ($admission_year == null || !is_int($admission_year)) {
    throw new ValidationException('無効な入学年度形式です。');
}
if (!isset($major_code)) {
    throw new ValidationException('専攻のコードは必須です。');
}
if ($major_code == null || !is_string($major_code)) {
    throw new ValidationException('無効な専攻のコード形式です。');
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
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
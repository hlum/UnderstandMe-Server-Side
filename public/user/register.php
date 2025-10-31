<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';


use Infrastructure\Persistence\MySQLUserRepository;
use Application\UseCases\UserUseCase;
use Domain\Entities\Role;
use Helpers\ApiKeyValidator;
use Helpers\Response;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST'])) {
    Response::send('error', 'Method Not Allowed. Use POST', 405);
}

// API Key validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


// POSTされたJSONデータを取得
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}




// Expected JSON structure
// {
// id: String,
// email: String,
// role: String('student' or 'teacher'),
// student_code: String not nullable,
// major_code: String not nullable,
// admission_year: String
// photo_url: String nullable (URL format)
// }




$user_id = $input['id'] ?? null;
$email = $input['email'] ?? null;
$photo_url = $input['photo_url'] ?? null;
$role = Role::from($input['role'] ?? 'student');
$student_code = $input['student_code'] ?? null;
$major_code = $input['major_code'] ?? null;
$admission_year = $input['admission_year'];


if (empty($user_id)) {
    Response::send('error', 'ユーザーIDは必須です。', 400);
}

if ($user_id == null || !is_string($user_id)) {
    Response::send('error', '無効なユーザーID形式です。', 400);
}

if (empty($email)) {
    Response::send('error', 'メールアドレスは必須です。', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::send('error', '無効なメールアドレス形式です。', 400);
}

if ($photo_url != null && !filter_var($photo_url, FILTER_VALIDATE_URL)) {
    Response::send('error', '無効なphoto_url形式です。', 400);
}

if (!isset($student_code)) {
    Response::send('error', 'student_codeを指定する必要があります。', 400);
}

if (!isset($major_code)) {
    Response::send('error', 'major_codeを指定する必要があります。', 400);
}

if (!isset($admission_year)) {
    Response::send('error', 'admission_yearを指定する必要があります。', 400);
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);

    $userUseCase->registerUser($user_id, $email, $role, $photo_url, $student_code, $admission_year, $major_code);

    Response::send('success', 'ユーザー登録が成功しました。', 200);
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
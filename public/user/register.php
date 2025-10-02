<?php

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

// Expected JSON structure
// {
    // user_id: String,
    // email: String,
    // role: String('student' or 'teacher'),
    // student_code: String not nullable,
    // fcm_token: String nullable,
// }


// POSTされたJSONデータを取得
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Response::send('error', '無効なJSONデータです。', 400);
}

$user_id = $input['user_id'] ?? null;
$email = $input['email'] ?? null;
$role = Role::from($input['role'] ?? 'student');
$fcm_token = $input['fcm_token'] ?? null;

if (empty($user_id)) {
    Response::send('error', 'ユーザーIDは必須です。', 400);
}

if($user_id == null || !is_string($user_id)){
    Response::send('error', '無効なユーザーID形式です。', 400);
}

if (empty($email)) {
    Response::send('error', 'メールアドレスは必須です。', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::send('error', '無効なメールアドレス形式です。', 400);
}
if (!isNullORValidFcmToken($fcm_token)) {
    Response::send('error', '無効なFCMトークン形式です。', 400);
}

$student_code = mb_substr($email, 0, strpos($email, '@'));
$grade = $student_code.trim(mb_substr($student_code, 0, 2), '0');
if((int)$grade != 0){
    $grade = (int)$grade;
}
$class_name = mb_substr($student_code, 2, 2);

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);

    $userUseCase->registerUser($user_id, $email, $role, $student_code, $grade, $class_name, $fcm_token);

    Response::send('success', 'ユーザー登録が成功しました。', 200);
} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}


function isNullORValidFcmToken(?string $token): bool {
    return $token === null || (is_string($token) && strlen($token) > 10);
}
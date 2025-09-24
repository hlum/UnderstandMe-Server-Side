<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ApiKeyValidator.php';


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

$email = $input['email'] ?? null;
$role = $input['role'] ?? 'student';
$fcm_token = $input['fcm_token'] ?? null;

if (empty($email)) {
    Response::send('error', 'メールアドレスは必須です。', 400);
}
if (!isValidEmail($email)) {
    Response::send('error', '無効なメールアドレス形式です。@jec.ac.jp以外のメールはご利用できません。', 400);
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




// DB operations
try {
    $db = new Database();

    $id = bin2hex(random_bytes(16));

    $db->insert_new_user($id, $email, $role, $student_code, $grade, $class_name, $fcm_token);
    Response::send('success', 'ユーザーが正常に保存されました。', 200);
} catch (Exception $e) {
    Response::send('error',  'Database operation failed. See server logs. \n ' . $e->getMessage(), 500);
}


function isValidEmail(string $email): bool {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) && str_ends_with($email, '@jec.ac.jp') && strlen($email) === 18;
}


function isNullORValidFcmToken(?string $token): bool {
    return $token === null || (is_string($token) && strlen($token) > 10);
}
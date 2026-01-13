<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Infrastructure\Persistence\MySQLUserRepository;
use Application\UseCases\UserUseCase;
use Domain\Entities\Role;
use Helpers\ApiKeyValidator;
use Helpers\Response;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);






// Expected JSON structure
// {
// id: String,
// name: String,
// email: String,
// role: String('student' or 'teacher'),
// student_code: String not nullable,
// major_code: String not nullable,
// admission_year: String
// photo_url: String nullable (URL format)
// }




try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST'])) {
        Response::send('fail', 'Method Not Allowed. Use POST', 405, null, 'validation_error');
    }

    // API Key validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::check($clientApiKey);


    // POSTされたJSONデータを取得
    $input = json_decode(file_get_contents('php://input'), true);


    $user_id = $input['id'] ?? null;
    $name = $input['name'] ?? null;
    $email = $input['email'] ?? null;
    $photo_url = $input['photo_url'] ?? null;
    $role = Role::from($input['role'] ?? 'student');
    $student_code = $input['student_code'] ?? null;
    $major_code = $input['major_code'] ?? null;
    $admission_year = $input['admission_year'] ?? null;


    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    if (empty($user_id)) {
        throw new ValidationException('ユーザーIDは必須です。');
    }

    if ($user_id == null || !is_string($user_id)) {
        throw new ValidationException('無効なユーザーID形式です。');
    }

    if (empty($name)) {
        throw new ValidationException('名前は必須です。');
    }

    if (empty($email)) {
        throw new ValidationException('メールアドレスは必須です。');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('無効なメールアドレス形式です。');
    }

    if ($photo_url != null && !filter_var($photo_url, FILTER_VALIDATE_URL)) {
        throw new ValidationException('無効なphoto_url形式です。');
    }

    if (!isset($student_code)) {
        throw new ValidationException('student_codeを指定する必要があります。');
    }

    if (!isset($major_code)) {
        throw new ValidationException('major_codeを指定する必要があります。');
    }

    if (!isset($admission_year)) {
        throw new ValidationException('admission_yearを指定する必要があります。');
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);

    // if the major code is zz then it is guest so we will create a random student code
    if($major_code == "zz") {
        $student_code = bin2hex(random_bytes(4));
    }

    $userUseCase->registerUser($user_id, $name, $email, $role, $photo_url, $student_code, $admission_year, $major_code);

    Response::send('success', 'ユーザー登録が成功しました。', 200);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
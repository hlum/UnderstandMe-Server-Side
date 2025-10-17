<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Infrastructure\Persistence\MySQLUserRepository;
use Application\UseCases\UserUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('error', 'Method not allowed. Use GET', 405);
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


$major_code = $_GET['major_code'] ?? null;
$admission_year = $_GET['admission_year'] ?? null;
$user_id = $_GET['id'] ?? null;
$email = $_GET['email'] ?? null;
$student_code = $_GET['student_code'] ?? null;


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}

try {
    $users = [];

    if (isset($user_id)) {
        $user = $userUseCase->findById($user_id);
        if ($user !== null) {
            $users = [$user];
        }
    } elseif (isset($email)) {
        $user = $userUseCase->findByEmail($email);
        if ($user !== null) {
            $users = [$user];
        }
    } elseif (isset($student_code)) {
        $user = $userUseCase->findByStudentCode($student_code);
        if ($user !== null) {
            $users = [$user];
        }
    } elseif (isset($admission_year) || isset($major_code)) {
        if (!(isset($admission_year) && isset($major_code))) {
            Response::send('error', 'admission_yearとmajor_codeは両方指定する必要があります。', 400);
        }
        $users = $userUseCase->findByMajorCodeAndAdmissionYear($major_code, $admission_year);
    } else {
        Response::send('error', 'user_id、email、student_code、admission_year+major_codeのいずれかを指定してください', 400);
    }

    if (empty($users)) {
        Response::send('error', 'ユーザーが見つかりませんでした', 404);
    }

    Response::send('success', 'ユーザーの取得に成功しました', 200, json_encode($users));

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), $e->getCode());
}

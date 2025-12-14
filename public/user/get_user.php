<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Infrastructure\Persistence\MySQLUserRepository;
use Application\UseCases\UserUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
}




try {
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


    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
}catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
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
            throw new ValidationException('admission_yearとmajor_codeは両方指定する必要があります。');
        }
        $users = $userUseCase->findByMajorCodeAndAdmissionYear($major_code, $admission_year);
    } else {
        throw new ValidationException('user_id、email、student_code、admission_year+major_codeのいずれかを指定してください');
    }

    Response::send('success', 'ユーザーの取得に成功しました', 200,$users);

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}

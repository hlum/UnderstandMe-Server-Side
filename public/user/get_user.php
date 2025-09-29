<?php
use Application\UseCases\UserUseCase;
use Domain\Entities\User;
use Infrastructure\Persistence\MySQLUserRepository;

require_once __DIR__ . '/../../src/Helpers/SafeRequire.php';
SafeRequire::requireFile(__DIR__. '/../../config/config.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Response.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Helpers/ApiKeyValidator.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Domain/Repositories/UserRepositoryInterface.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Infrastructure/Persistence/MySQLUserRepository.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Domain/Repositories/UserRepositoryInterface.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Application/UseCases/UserUseCase.php');
SafeRequire::requireFile(__DIR__ . '/../../src/Domain/Entities/User.php');



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if(!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
    Response::send('error', 'Method not allowed. Use GET', 405);
}


$headers = getallheaders();
// API KEY Validation
$headers = getallheaders();
$clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
ApiKeyValidator::check($clientApiKey);


$class_name      = $_GET['class_name']      ?? null;
$admission_year  = $_GET['admission_year']  ?? null;
$user_id         = $_GET['user_id']         ?? null;
$email           = $_GET['email']           ?? null;
$student_code    = $_GET['student_code']    ?? null;


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $userRepository = new MySQLUserRepository($connection);
    $userUseCase = new UserUseCase($userRepository);
} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
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
    } elseif (isset($admission_year) || isset($class_name)) {
        if (!(isset($admission_year) && isset($class_name))) {
            Response::send('error', 'admission_yearとclass_nameは両方指定する必要があります。', 400);
        }
        $users = $userUseCase->findByClassNameAndAdmissionYear($class_name, $admission_year);
    } else {
        Response::send('error', 'user_id、email、student_code、admission_year+class_nameのいずれかを指定してください', 400);
    }

    if (empty($users)) {
        Response::send('error', 'ユーザーが見つかりませんでした', 404);
    }

    Response::send('success', 'ユーザーの取得に成功しました', 200, json_encode($users));

} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}

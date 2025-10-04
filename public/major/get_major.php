<?php

require __DIR__ . '/../../vendor/autoload.php';
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLMajorRepository;
use Application\UseCases\MajorUseCase;
use Infrastructure\Persistence\MySQLUserRepository;

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


/* Possible queries
by id
by teacher_id
by class_name and admission_year
by student_id (get the major of a specific student)
*/


$id = $_GET['id'] ?? null;
$teacher_id = $_GET['teacher_id'] ?? null;
$class_name   = $_GET['class_name']   ?? null;
$admission_year    = $_GET['admission_year']    ?? null;
$student_id  = $_GET['student_id']  ?? null;


try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $majorRepository = new MySQLMajorRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $majorUseCase = new MajorUseCase($majorRepository, $userRepository);

} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}


try {
    $majors = [];

    if (isset($id)) {
        $major = $majorUseCase->findById($id);
        $majors[] = $major;
    } elseif (isset($class_name) && isset($admission_year)) {

        if(!is_string($class_name)){
            Response::send('error', '無効なクラス名形式です。', 400);
        }
        if(!is_numeric($admission_year)){
            Response::send('error', '無効な入学年度形式です。', 400);
        }
        $admission_year = (int)$admission_year;
        $majors[] = $majorUseCase->findByClassNameAndAdmissionYear($class_name, $admission_year);

    } elseif (isset($teacher_id)) {

        if(!is_string($teacher_id)){
            Response::send('error', '無効な教師ID形式です。', 400);
        }
        $majors[] = $majorUseCase->getMajorsByTeacherId($teacher_id);

    } elseif (isset(($student_id))) {
        if(!is_string($student_id)){
            Response::send('error', '無効な学生ID形式です。', 400);
        }
        // Assuming a method getMajorByStudentId exists in MajorUseCase
        $major = $majorUseCase->getMajorByStudentId($student_id);
        if ($major !== null) {
            $majors[] = $major;
        }
    } else {
        Response::send('error', '少なくとも1つのクエリパラメータ（id、class_nameとadmission_year、student_id）を指定してください。', 400);
    }

    Response::send('success', '専攻の取得に成功しました。', 200, json_encode($majors));

} catch (InvalidArgumentException $e) {
    Response::send('error', $e->getMessage(), 404);
} catch (Throwable $e) {
    Response::send('error', $e->getMessage(), 500);
}
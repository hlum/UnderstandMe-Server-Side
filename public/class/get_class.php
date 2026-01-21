<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../vendor/autoload.php';

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Helpers\Response;
use Helpers\ApiKeyValidator;
use Infrastructure\Persistence\MySQLClassRepository;
use Application\UseCases\ClassUseCase;
use Infrastructure\Persistence\MySQLStudentClassEnrollmentRepository;
use Infrastructure\Persistence\MySQLUserRepository;



try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET'])) {
        Response::send('fail', 'Method not allowed. Use GET', 405, null, 'validation_error');
    }


    $headers = getallheaders();
    // API KEY Validation
    $headers = getallheaders();
    $clientApiKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::check($clientApiKey);


    /* Possible queries
    by id
    by teacher_id
    by class_code
    by major_code and admission_year
    by student_id (get the class of a specific student)
    */


    $id = $_GET['id'] ?? null;
    $teacher_id = $_GET['teacher_id'] ?? null;
    $major_code = $_GET['major_code'] ?? null;
    $admission_year = $_GET['admission_year'] ?? null;
    $student_id = $_GET['student_id'] ?? null;
    $class_code = $_GET['class_code'] ?? null;

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $classRepository = new MySQLClassRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $studentClassEnrollmentRepo = new MySQLStudentClassEnrollmentRepository($connection);
    $classUseCase = new ClassUseCase($classRepository, $userRepository, $studentClassEnrollmentRepo);

}catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}


try {
    $classes = [];

    if (isset($id)) {
        $class = $classUseCase->findById($id);
        $classes[] = $class;
    } elseif (isset($major_code) && isset($admission_year)) {

        if (!is_string($major_code)) {
            throw new ValidationException('無効な専攻のコード形式です。');
        }
        if (!is_numeric($admission_year)) {
            throw new ValidationException('無効な入学年度形式です。');
        }
        $admission_year = (int) $admission_year;
        $classes = $classUseCase->findByMajorCodeAndAdmissionYear($major_code, $admission_year);

    } elseif (isset($teacher_id)) {

        if (!is_string($teacher_id)) {
            throw new ValidationException('無効な教師ID形式です。');
        }
        $classes = $classUseCase->getClassesByTeacherId($teacher_id);

    } elseif (isset(($student_id))) {
        if (!is_string($student_id)) {
            throw new ValidationException('無効な学生ID形式です。');
        }

        $classes = $classUseCase->getClassesByStudentId($student_id);

    } elseif (isset($class_code)) {
        $class = $classUseCase->findByClassCode($class_code);
        if ($class !== null) {
            $classes[] = $class;
        }

    } else {
        throw new ValidationException('少なくとも1つのクエリパラメータ（id、major_code と admission_year、student_id）を指定してください。');
    }

    Response::send('success', 'クラスの取得に成功しました。', 200, $classes);

} catch(AppException $e) {
    Response::send('fail', $e->getMessage(), $e->getStatusCode(), null, $e->getErrorType());
} catch (Throwable $e) {
    error_log('サーバー内部エラー: ' . $e->getMessage());
    Response::send('error', 'サーバー内部エラーが発生しました。', 500, null, 'server_error');
}
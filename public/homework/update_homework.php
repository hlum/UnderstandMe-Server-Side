<?php

use Application\CustomExceptions\AppException;
use Application\CustomExceptions\ValidationException;
use Application\UseCases\HomeworkUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLClassRepository;
use Infrastructure\Persistence\MySQLHomeworkRepository;
use Infrastructure\Persistence\MySQLUserRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: UPDATE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {

    if (!in_array($_SERVER['REQUEST_METHOD'], ['UPDATE'])) {
        Response::send('fail', 'Method not allowed. Use UPDATE', 405, null, 'validation_error');
    }

    $headers = getallheaders();
    $clientAPIKey = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    ApiKeyValidator::checkTeacherKey($clientAPIKey);


    $input = json_decode(file_get_contents('php://input'), true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ValidationException('無効なJSONデータです。');
    }

    $homework_id = $input['homework_id'] ?? null;
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? null;
  

    if (!isset($homework_id)) {
        throw new ValidationException('homework_idは必須です。');
    }

    if ($homework_id == null || !is_string($homework_id)) {
        throw new ValidationException('無効なhomework_id形式です。');
    }

    $fieldsToUpdate = [];
    if (isset($title)) {
        if ($title == null || !is_string($title)) {
            throw new ValidationException('無効なtitle形式です。');
        }
        $fieldsToUpdate['title'] = $title;
    }

    if (array_key_exists('description', $input)) {
        $description = $input['description'];
        if ($description !== null && !is_string($description)) {
            throw new ValidationException('無効なdescription形式です。');
        }
        $fieldsToUpdate['description'] = $description;
    }


    if (array_key_exists('due_date', $input)) {
        $due_date = $input['due_date']; // null or string

        if ($due_date !== null && !is_string($due_date)) {
            throw new ValidationException('無効なdue_date形式です。');
        }

        // null の場合 → 日付を削除
        // string の場合 → 日付を更新
        $fieldsToUpdate['due_date'] = $due_date;
    }


    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $homeworkRepository = new MySQLHomeworkRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $classRepository = new MySQLClassRepository($connection);
    $homeworkUseCase = new HomeworkUseCase($homeworkRepository, $userRepository, $classRepository);
    $homeworkUseCase->update($homework_id, $fieldsToUpdate);

    Response::send('success', '課題が正常に更新されました。', 200);

} catch (AppException $appException) {
    Response::send('fail', $appException->getMessage(), $appException->getStatusCode(), null, $appException->getErrorType());
} catch (Exception $e) {
    Response::send('error', 'サーバーエラーが発生しました。', 500, null, 'server_error');
}
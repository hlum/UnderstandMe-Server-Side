<?php

require __DIR__ . '/../../vendor/autoload.php';
use Application\UseCases\JobUseCase;
use Helpers\ApiKeyValidator;
use Helpers\Response;
use Infrastructure\Persistence\MySQLJobRepository;
use Infrastructure\Persistence\MySQLUserRepository;
use Infrastructure\Persistence\MySQLProjectRepository;

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
by Id
by userID
get all with limit and offset
*/

$job_id = $_GET['id'] ?? null;
$user_id = $_GET['user_id'] ?? null;
$limit   = $_GET['limit']   ?? 100;
$offset  = $_GET['offset']  ?? 0;

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $jobRepository = new MySQLJobRepository($connection);
    $userRepository = new MySQLUserRepository($connection);
    $projectRepository = new MySQLProjectRepository($connection);
    $jobUseCase = new JobUseCase($jobRepository, $userRepository, $projectRepository);
} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}


try {
    $jobs = [];

    if (isset($job_id)) {
        $job = $jobUseCase->findById($job_id);
        if ($job !== null) {
            $jobs = [$job];
        }
    } elseif (isset($user_id)) {
        $job = $jobUseCase->findByUserId($user_id);
        if ($job !== null) {
            $jobs = [$job];
        }
    } else {
        if (!is_numeric($limit) || !is_numeric($offset)) {
            Response::send('error', 'limitとoffsetは数値である必要があります。', 400);
        }
        $limit = (int)$limit;
        $offset = (int)$offset;
        $jobs = $jobUseCase->getAllJobs($limit, $offset);
    }

    Response::send('success', 'Jobsの取得に成功しました。', 200, json_encode($jobs));

} catch (Throwable $e) {
    Response::send('error',  $e->getMessage(), 500);
}
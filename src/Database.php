<?php

class Database {
    private mysqli $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->connection->connect_error) {
            Response::send('error', 'データベース接続エラー \n 詳細 \n' . $this->connection->connect_error, 500);
        }
    }


    public function insert_new_user(
        string $id,
        string $email,
        string $role,
        string $student_code,
        string $grade,
        string $class_name,
        ?string $fcm_token

    ): void {

        $stmt = $this->connection->prepare("INSERT INTO users (id, email, role, student_code, grade, class_name, fcm_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
        if ($stmt === false) {
            Response::send('error', 'ステートメントの準備に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        $stmt->bind_param('sssssss', $id, $email, $role, $student_code, $grade, $class_name, $fcm_token);

        if ($stmt === false) {
            Response::send('error', 'パラメータのバインドに失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        if ($stmt->execute() === false) {
            Response::send('error', 'クエリの実行に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
    }


    public function insert_project(
        string $id,
        string $job_id,
        string $user_id,
        string $github_url
        ): void {

        $stmt = $this->connection->prepare("INSERT INTO projects (id, job_id, user_id, github_url) VALUES (?, ?, ?, ?)");
        
        if ($stmt === false) {
            Response::send('error', 'ステートメントの準備に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        $stmt->bind_param('ssss', $id, $job_id, $user_id, $github_url);

        if ($stmt === false) {
            Response::send('error', 'パラメータのバインドに失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        if ($stmt->execute() === false) {
            Response::send('error', 'クエリの実行に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
    }


    public function insert_job(
        string $id,
        string $user_id,
        string $project_id,
        string $prompt,
        string $status
    ) {
        $stmt = $this->connection->prepare("INSERT INTO jobs (id, user_id, project_id, prompt, status) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            Response::send('error', 'ステートメントの準備に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        $stmt->bind_param('sssss', $id, $user_id, $project_id, $prompt, $status);

        if ($stmt === false) {
            Response::send('error', 'パラメータのバインドに失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        if ($stmt->execute() === false) {
            Response::send('error', 'クエリの実行に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }

    }

    public function change_job_status(
        string $job_id,
        string $new_status
    ): void {
        $stmt = $this->connection->prepare("UPDATE jobs SET status = ? WHERE id = ?");
        
        if ($stmt === false) {
            Response::send('error', 'ステートメントの準備に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        $stmt->bind_param('ss', $new_status, $job_id);

        if ($stmt === false) {
            Response::send('error', 'パラメータのバインドに失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        if ($stmt->execute() === false) {
            Response::send('error', 'クエリの実行に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
    }


    public function get_processing_job_count(): int {
        $result = $this->run_query("SELECT COUNT(*) as count FROM jobs WHERE status = 'processing'");

        if ($result === false) {
            Response::send('error', 'クエリの実行に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        $row = $result->fetch_assoc();

        if($row === null || !isset($row['count'])) {
            Response::send('error', 'processingのJob数取得に失敗' . '\n 詳細 \n' . $this->connection->error, 500);
        }

        return (int)$row['count'];
    }


    private function run_query(string $query): mysqli_result|false {
        return $this->connection->query($query);
    }


    public function close(): void {
    if ($this->connection && $this->connection->ping()) {
        $this->connection->close();
    }
}

    // Correct destructor syntax
    public function __destruct() {
        $this->close();
    }
}
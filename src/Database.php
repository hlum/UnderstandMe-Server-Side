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
        $query = "INSERT INTO users (id, email, role, student_code, grade, class_name, fcm_token) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $types = 'sssssss';
        $params = [$id, $email, $role, $student_code, $grade, $class_name, $fcm_token];
        $error_message = 'ユーザーの保存に失敗しました';
        $this->execute($query, $types, $params, $error_message);
    }


    public function insert_project(
        string $id,
        string $user_id,
        string $github_file_link
        ): void {

        $query = "INSERT INTO projects (id, user_id, github_file_link) VALUES (?, ?, ?)";
        $types = 'sss';
        $params = [$id, $user_id, $github_file_link];
        $error_message = 'プロジェクトの保存に失敗しました';
        $this->execute($query, $types, $params, $error_message);    
    }

    public function insert_job(
        string $id,
        string $user_id,
        string $project_id,
        string $prompt,
        string $status
    ) {
        $query = "INSERT INTO jobs (id, user_id, project_id, prompt, status) VALUES (?, ?, ?, ?, ?)";
        $types = 'sssss';
        $params = [$id, $user_id, $project_id, $prompt, $status];
        $error_message = 'ジョブの保存に失敗しました';
        $this->execute($query, $types, $params, $error_message);
    }


    public function insert_result(
        string $id,
        string $questions
    ) {
        $query = "INSERT INTO results (id, questions) VALUES (?, ?)";
        $types = 'ss';
        $params = [$id, $questions];
        $error_message = '結果の保存に失敗しました';
        $this->execute($query, $types, $params, $error_message);
    }

    public function update_result_id_in_job(
        string $job_id,
        string $result_id
    ): void {
        $query = "UPDATE jobs SET result_id = ? WHERE id = ?";
        $types = 'ss';
        $params = [$result_id, $job_id];
        $error_message = 'ジョブの結果IDの更新に失敗しました';
        $this->execute($query, $types, $params, $error_message);
    }

    public function insert_answer(
        string $id,
        string $user_id,
        string $job_id,
        string $result_id,
        int $question_index,
        string $user_answer,
        string $correct_answer,
        int $score
    ): void {
        $query = "INSERT INTO answers (id, user_id, job_id, result_id, question_index, user_answer, correct_answer, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $types = 'sssssssi';
        $params = [$id, $user_id, $job_id, $result_id, $question_index, $user_answer, $correct_answer, $score];
        $error_message = '回答の保存に失敗しました';
        $this->execute($query, $types, $params, $error_message);
    }




    public function change_job_status(
        string $job_id,
        string $new_status
    ): void {
        $query = "UPDATE jobs SET status = ? WHERE id = ?";
        $types = 'ss';
        $params = [$new_status, $job_id];
        $error_message = 'ジョブのステータス更新に失敗しました';
        $this->execute($query, $types, $params, $error_message);
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


    private function execute(string $query, string $types, array $params, string $error_message): void {
        $stmt = $this->connection->prepare($query);

        if ($stmt === false) {
            Response::send('error', 'ステートメントの準備に失敗しました。詳細: ' . $this->connection->error, 500);
        }

        // Unpack array into references for bind_param
        $stmt->bind_param($types, ...$params);

        if ($stmt === false) {
            Response::send('error', 'パラメータのバインドに失敗しました。詳細: ' . $this->connection->error, 500);
        }

        if ($stmt->execute() === false) {
            Response::send('error', $error_message . '。詳細: ' . $this->connection->error, 500);
        }

        $stmt->close();
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
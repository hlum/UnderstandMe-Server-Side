<?php

class Database {
    private mysqli $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->connection->connect_error) {
            Response::send('error', 'データベース接続エラー \n 詳細 \n' . $this->connection->connect_error, 500);
        }
    }


    public function insert_new_user(string $email, ?string $fcm_token): void {
        // Generate a unique ID (UUID v4 alternative)
        $id = bin2hex(random_bytes(16));
        
        $stmt = $this->connection->prepare("INSERT INTO users (id, email, fcm_token) VALUES (?, ?, ?)");
        
        if ($stmt === false) {
            Response::send('error', 'ステートメントの準備に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        $stmt->bind_param('sss', $id, $email, $fcm_token);

        if ($stmt === false) {
            Response::send('error', 'パラメータのバインドに失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
        if ($stmt->execute() === false) {
            Response::send('error', 'クエリの実行に失敗しました。' . '\n 詳細 \n' . $this->connection->error, 500);
        }
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
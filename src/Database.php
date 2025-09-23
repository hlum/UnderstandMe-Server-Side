<?php

class Database {
    private mysqli $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->connection->connect_error) {
            Response::send('error', 'データベース接続エラー \n 詳細 \n' . $this->connection->connect_error, 500);
        }
    }


    public function query(string $query): mysqli_result {
        $result = $this->connection->query($query);
        $error_message = $query.'で失敗しました。'.'\n 詳細 \n'.$this->connection->error;
        if (!$result) {
            Response::send('error', $error_message, 500);
        }

        return $result;
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
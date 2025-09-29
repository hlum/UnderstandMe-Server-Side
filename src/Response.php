<?php

class Response {
    public static function send(string $status, string $message, int $code = 200, ?string $data = null) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['status' => $status, 'message' => $message, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );
        exit();
    }
    
}
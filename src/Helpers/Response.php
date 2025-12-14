<?php

namespace Helpers;

class Response
{
    public static function send(
        string $status,
        string $message,
        int $code = 200,
        $data = null,           // ← 型を string から外す
        ?string $error_type = null,
        bool $exit = true
    ) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data       // ← 配列OK
        ];

        if ($error_type !== null) {
            $response['error_type'] = $error_type;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);

        if ($exit) {
            exit();
        }
    }
}

<?php

namespace Helpers;

class Response
{
    public static function send(
        string $status,
        string $message,
        int $code = 200,
        ?string $data = null,
        ?string $error_type = null,
        bool $exit = true
    ) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        
        // error_type が指定されている場合のみレスポンスに追加
        if ($error_type !== null) {
            $response['error_type'] = $error_type;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
        if ($exit) {
            exit();
        }
    }

}
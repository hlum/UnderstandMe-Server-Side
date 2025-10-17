<?php
namespace Helpers;

require_once __DIR__ . '/../../config/config.php';

class ApiKeyValidator {
    public static function check(?string $clientApiKey) {
        if (empty($clientApiKey)) {
            Response::send('error', 'APIキーが提供されていません。', 400);
        }

        if (!hash_equals(API_KEY, $clientApiKey)) {
            Response::send('error', 'アクセスが拒否されました。無効なAPIキーです。', 403);
        }
    }

    public static function checkTeacherKey(?string $teacherApiKey) {
        if (empty($teacherApiKey)) {
            Response::send('error', 'APIキーが提供されていません。', 400);
        }

        if(!hash_equals($teacherApiKey,TEACHER_API_KEY)) {
            Response::send('error', 'アクセスが拒否されました。無効なAPIキーです。教師専用のAPIKEYが必要です。', 403);
        }
    }
}
<?php
namespace Helpers;

use Application\CustomExceptions\ValidationException;
use Application\CustomExceptions\ForbiddenException;

require_once __DIR__ . '/../../config/config.php';

class ApiKeyValidator
{
    public static function check(?string $clientApiKey)
    {
        if (empty($clientApiKey)) {
            throw new ValidationException('APIキーが提供されていません。');
        }

        if (!hash_equals(API_KEY, $clientApiKey)) {
            throw new ForbiddenException('アクセスが拒否されました。無効なAPIキーです。');
        }
    }

    public static function checkTeacherKey(?string $teacherApiKey)
    {
        if (empty($teacherApiKey)) {
            throw new ValidationException('APIキーが提供されていません。');
        }

        if (!hash_equals($teacherApiKey, TEACHER_API_KEY)) {
            throw new ForbiddenException('アクセスが拒否されました。無効なAPIキーです。教師専用のAPIKEYが必要です。');
        }
    }
}
<?php
namespace Helpers;

use Application\CustomExceptions\ValidationException;
use Application\CustomExceptions\ForbiddenException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;

require_once __DIR__ . '/../../config/config.php';

class ApiKeyValidator
{
    private static $firebaseAuth = null;

    /**
     * Initialize Firebase Auth (lazy loading)
     */
    private static function getFirebaseAuth()
    {
        if (self::$firebaseAuth === null) {
            $factory = (new Factory)->withServiceAccount(FIREBASE_SERVICE_ACCOUNT_PATH);
            self::$firebaseAuth = $factory->createAuth();
        }
        return self::$firebaseAuth;
    }

    /**
     * Validate Firebase ID token or fallback to static API key
     */
    public static function check(?string $authToken)
    {
        if (empty($authToken)) {
            throw new ValidationException('認証トークンが提供されていません。');
        }

        // Check if it's the old static API key (backward compatibility)
        if (hash_equals(API_KEY, $authToken)) {
            // Valid static API key - allow access
            return null;
        }

        // Try to validate as Firebase ID token
        try {
            $auth = self::getFirebaseAuth();
            $verifiedIdToken = $auth->verifyIdToken($authToken);

            // Token is valid - return the user ID for use in endpoints
            $uid = $verifiedIdToken->claims()->get('sub');
            return $uid;
        } catch (\Exception $e) {
            // Token verification failed
            error_log('Firebase token verification failed: ' . $e->getMessage());
            throw new ForbiddenException('アクセスが拒否されました。無効な認証トークンです。');
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
<?php

namespace Helpers;


class NotificationHandler
{
    public string $firebaseProjectID; // Firebase Project ID
    public string $keyFilePath; // service-account.json のパス

    public function __construct(string $firebaseProjectID, string $keyFilePath = __DIR__ . '/../../config/service-account.json')
    {
        $this->firebaseProjectID = $firebaseProjectID;
        $this->keyFilePath = $keyFilePath;

        if ($this->keyFilePath === false || !file_exists($this->keyFilePath)) {
            throw new \Exception("❌ service-account.jsonファイルが見つかりません: {$keyFilePath}");
        }
    }


    function sendFCMNotification($fcmToken, $title, $body)
    {
        $projectId = $this->firebaseProjectID;
        $keyFilePath = $this->keyFilePath;

        // Step 1: Get OAuth 2.0 access token
        $jwt = $this->generateJWT($keyFilePath, $projectId);
        $accessToken = $this->getAccessToken($jwt);

        // Step 2: Build the notification payload
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $postData = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'android' => [
                    'priority' => 'high'
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10'
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default'
                        ]
                    ]
                ]
            ]
        ];

        $headers = [
            "Authorization: Bearer {$accessToken}",
            'Content-Type: application/json; charset=UTF-8'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);


        if ($response === false) {
            throw new \Exception("❌ CURL error: " . curl_error($ch));
        }


        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['response' => $response, 'code' => $httpCode];
    }

    function generateJWT($keyFilePath, $projectId)
    {
        try {

            $serviceAccount = json_decode(file_get_contents($keyFilePath), true);

            $now = time();
            $expiration = $now + 3600;

            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claim = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $expiration
            ];

            $jwtHeader = $this->base64UrlEncode(json_encode($header));
            $jwtClaim = $this->base64UrlEncode(json_encode($claim));

            $signatureInput = $jwtHeader . '.' . $jwtClaim;

            // Sign with private key
            $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
            openssl_sign($signatureInput, $signature, $privateKey, 'sha256WithRSAEncryption');
            openssl_free_key($privateKey);

            return $signatureInput . '.' . $this->base64UrlEncode($signature);
        } catch (\Exception $e) {
            throw new \Exception("❌ JWT生成に失敗しました: " . $e->getMessage());
        }
    }

    function getAccessToken($jwt)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_POST, true);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (empty($data['access_token'])) {
            throw new \Exception("❌ Failed to obtain access token. Response: " . json_encode($data));
        }
        return $data['access_token'];
    }

    function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

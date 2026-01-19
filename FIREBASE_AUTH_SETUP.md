# Firebase Authentication Setup

## Changes Made

The API has been updated to accept **Firebase ID tokens** instead of only the static API key. This improves security by:
- Eliminating exposed static API keys in browser network traffic
- Validating user identity on each request
- Using short-lived, user-specific tokens that expire after 1 hour

## Installation Steps

### 1. Install Firebase Admin SDK

Run this command in the API directory:

```bash
cd /Users/cmstudent/School/卒制/API/sotsusei
composer install
```

This will install the `kreait/firebase-php` package added to `composer.json`.

### 2. Verify Service Account File

Make sure `/Users/cmstudent/School/卒制/API/sotsusei/config/service-account.json` exists and contains your Firebase service account credentials.

### 3. Deploy Changes

If using Docker:
```bash
docker-compose down
docker-compose up --build -d
```

If running directly:
```bash
# Restart your PHP-FPM or web server
sudo systemctl restart php-fpm
# or
sudo service nginx restart
```

## How It Works

### Before (Old Method)
```
Browser → API with static key "afskjw42y8571wsdkls514amoiejojsdk" → Server validates static key
```
❌ Static key visible in browser network tab
❌ Key can be stolen and reused

### After (New Method)
```
Browser → API with Firebase ID token "eyJhbGci..." → Server validates token with Firebase
```
✅ Token is user-specific and expires in 1 hour
✅ Even if stolen, token expires quickly
✅ Server knows which user made the request

## Backward Compatibility

The API still accepts the old static API key for backward compatibility during migration:
- Old clients using static API key: **Still works** ✅
- New clients using Firebase tokens: **Works** ✅

You can remove the static API key fallback after all clients are updated.

## Testing

1. **Test with old API key** (should still work):
```bash
curl -H "Authorization: afskjw42y8571wsdkls514amoiejojsdk" \
  https://api.hlumaungphyo.site/user/get_user.php?id=test_user_id
```

2. **Test with Firebase token** (from web app):
   - Open browser DevTools → Network tab
   - Make any API call from the web app
   - Check the Authorization header - should show a long JWT token starting with `eyJ`
   - API should accept this token

## Removing the Static API Key (Future)

Once all clients (iOS, Android, Web) are updated to use Firebase tokens, you can remove backward compatibility:

In `ApiKeyValidator.php`, remove lines 36-40:
```php
// Remove this block after migration
if (hash_equals(API_KEY, $authToken)) {
    return null;
}
```

## Troubleshooting

### Error: "Class 'Kreait\Firebase\Factory' not found"
**Solution**: Run `composer install` in the API directory

### Error: "Firebase token verification failed"
**Possible causes**:
- Token expired (they expire after 1 hour - this is normal)
- Service account file is missing or invalid
- Token is from wrong Firebase project

**Solution**: Check error logs for details:
```bash
tail -f /var/log/nginx/error.log
# or
tail -f /path/to/your/php/error.log
```

### Error: "認証トークンが提供されていません"
**Solution**: Make sure the Authorization header is being sent with requests

## Getting User ID in Endpoints

The `ApiKeyValidator::check()` method now returns the Firebase user ID (UID):

```php
// In your endpoint files
$uid = ApiKeyValidator::check($clientApiKey);

if ($uid !== null) {
    // Request was made with Firebase token - $uid contains the user ID
    error_log("Request from user: " . $uid);
} else {
    // Request was made with static API key (old method)
    // You may want to get user_id from query params instead
}
```

This allows you to:
- Track which user made each request
- Implement user-specific rate limiting
- Add additional authorization checks (e.g., "can this user access this resource?")

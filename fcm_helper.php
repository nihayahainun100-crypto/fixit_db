<?php
/**
 * FCM HTTP v1 Helper
 * Pakai Google OAuth2 Service Account — BUKAN legacy Server Key
 *
 * Cara setup:
 * 1. Firebase Console → Project Settings → Service Accounts
 * 2. Klik "Generate new private key" → download file JSON
 * 3. Simpan file JSON itu di server (misal: config/service_account.json)
 * 4. Set path di FCM_SERVICE_ACCOUNT_PATH di bawah
 */

define('FCM_SERVICE_ACCOUNT_PATH', __DIR__ . '/config/service_account.json');
define('FCM_PROJECT_ID', 'fixitservice-41067');
define('FCM_V1_URL', 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send');

/**
 * Ambil OAuth2 access token dari service account JSON
 * Token ini berlaku 1 jam, di-cache ke file supaya tidak request ulang terus
 */
function getFcmAccessToken(): ?string {
    $cacheFile = sys_get_temp_dir() . '/fcm_token_cache.json';

    // Pakai cache jika masih valid (kurang dari 55 menit)
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        if ($cache && isset($cache['token'], $cache['expires_at'])) {
            if (time() < $cache['expires_at']) {
                return $cache['token'];
            }
        }
    }

    // Load service account
    if (!file_exists(FCM_SERVICE_ACCOUNT_PATH)) {
        error_log('FCM: service_account.json tidak ditemukan di ' . FCM_SERVICE_ACCOUNT_PATH);
        return null;
    }

    $sa = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT_PATH), true);
    if (!$sa || !isset($sa['private_key'], $sa['client_email'])) {
        error_log('FCM: service_account.json tidak valid');
        return null;
    }

    // Buat JWT untuk request OAuth2 token
    $now = time();
    $header    = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claimSet  = base64url_encode(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    $toSign = "$header.$claimSet";
    $privateKey = $sa['private_key'];
    openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $jwt = "$toSign." . base64url_encode($signature);

    // Request access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("FCM OAuth2 error ($httpCode): $result");
        return null;
    }

    $tokenData = json_decode($result, true);
    $accessToken = $tokenData['access_token'] ?? null;
    if (!$accessToken) return null;

    // Simpan ke cache
    file_put_contents($cacheFile, json_encode([
        'token'      => $accessToken,
        'expires_at' => $now + 3300, // 55 menit
    ]));

    return $accessToken;
}

/**
 * Kirim FCM v1 push notification
 *
 * @param string $fcmToken   Device token penerima
 * @param string $title      Judul notifikasi
 * @param string $body       Isi notifikasi
 * @param array  $data       Data payload tambahan (optional)
 * @return array ['success' => bool, 'message' => string]
 */
function sendFcmNotification(string $fcmToken, string $title, string $body, array $data = []): array {
    $accessToken = getFcmAccessToken();
    if (!$accessToken) {
        return ['success' => false, 'message' => 'Gagal ambil FCM access token. Cek service_account.json'];
    }

    $payload = json_encode([
        'message' => [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound'        => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id'   => 'fixit_channel',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound'             => 'default',
                        'content-available' => 1,
                    ],
                ],
            ],
            'data' => array_map('strval', $data), // FCM data harus string semua
        ],
    ]);

    $ch = curl_init(FCM_V1_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("FCM v1 response ($httpCode): $result");

    if ($httpCode === 200) {
        return ['success' => true, 'message' => 'Notifikasi terkirim'];
    }

    $err = json_decode($result, true);
    $errMsg = $err['error']['message'] ?? "HTTP $httpCode";
    return ['success' => false, 'message' => "FCM error: $errMsg"];
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>

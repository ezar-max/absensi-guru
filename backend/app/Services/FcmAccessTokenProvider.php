<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

class FcmAccessTokenProvider
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function getAccessToken(): string
    {
        $credentialsPath = config('services.fcm.credentials');
        $resolvedPath = $credentialsPath
            ? (realpath($credentialsPath) ?: realpath(base_path($credentialsPath)))
            : false;

        if (!$resolvedPath || !is_file($resolvedPath) || !is_readable($resolvedPath)) {
            throw new FcmMessagingException('File service account FCM belum dikonfigurasi atau tidak dapat dibaca.');
        }

        try {
            $credentials = new ServiceAccountCredentials(self::SCOPE, $resolvedPath);
            $token = $credentials->fetchAuthToken()['access_token'] ?? null;
        } catch (\Exception $exception) {
            throw new FcmMessagingException('Gagal mendapatkan access token FCM. Periksa service account dan izin FCM API.', 0, $exception);
        }

        if (!$token) {
            throw new FcmMessagingException('Google tidak memberikan access token FCM.');
        }

        return $token;
    }
}

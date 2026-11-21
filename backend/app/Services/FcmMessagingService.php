<?php

namespace App\Services;

use App\Models\AttendanceReminder;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FcmMessagingService
{
    public function __construct(private readonly FcmAccessTokenProvider $accessTokenProvider)
    {
    }

    public function sendReminder(User $user, AttendanceReminder $reminder): int
    {
        $tokens = $user->pushTokens()->where('platform', 'android')->get();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $projectId = config('services.fcm.project_id');
        if (!$projectId) {
            throw new FcmMessagingException('FCM_PROJECT_ID belum diatur pada konfigurasi server.');
        }

        $accessToken = $this->accessTokenProvider->getAccessToken();
        $sent = 0;

        foreach ($tokens as $pushToken) {
            try {
                $response = Http::acceptJson()
                    ->timeout(15)
                    ->withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $pushToken->token,
                            'notification' => [
                                'title' => $reminder->title,
                                'body' => $reminder->message,
                            ],
                            'data' => [
                                'reminder_id' => (string) $reminder->id,
                                'url' => '/(tabs)/rating',
                            ],
                            'android' => [
                                'priority' => 'HIGH',
                                'notification' => [
                                    'channel_id' => 'reminders',
                                ],
                            ],
                        ],
                    ]);
            } catch (ConnectionException $exception) {
                throw new FcmMessagingException('Tidak dapat terhubung ke Firebase Cloud Messaging.', 0, $exception);
            }

            if ($response->successful()) {
                $sent++;
                continue;
            }

            $isUnregistered = $response->json('error.status') === 'NOT_FOUND'
                || collect($response->json('error.details', []))
                    ->contains(fn ($detail) => is_array($detail) && ($detail['errorCode'] ?? null) === 'UNREGISTERED');

            if ($isUnregistered) {
                $pushToken->delete();
                continue;
            }

            throw new FcmMessagingException("FCM menolak pengiriman notifikasi (HTTP {$response->status()}).");
        }

        return $sent;
    }
}

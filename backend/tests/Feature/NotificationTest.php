<?php

namespace Tests\Feature;

use App\Models\AttendanceReminder;
use App\Models\Period;
use App\Models\PushToken;
use App\Models\User;
use App\Services\FcmAccessTokenProvider;
use App\Services\FcmMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_read_and_mark_attendance_reminders(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $secretary = User::create([
            'name' => 'Sekretaris',
            'nis' => '1022001',
            'email' => 'sekretaris@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
        $period = Period::create([
            'label' => 'Jam ke-1',
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
        ]);

        $reminder = AttendanceReminder::create([
            'user_id' => $secretary->id,
            'created_by' => $admin->id,
            'period_id' => $period->id,
            'title' => 'Pengingat Absensi Jam ke-1',
            'message' => 'Segera isi absensi.',
        ]);

        $token = $secretary->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.title', 'Pengingat Absensi Jam ke-1');

        $this->withHeaders($headers)
            ->postJson("/api/notifications/{$reminder->id}/read")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($reminder->fresh()->read_at);
    }

    public function test_secretary_can_register_and_remove_only_their_own_device_token(): void
    {
        $secretary = User::create([
            'name' => 'Sekretaris',
            'nis' => '1022002',
            'email' => 'sekretaris2@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
        $otherSecretary = User::create([
            'name' => 'Sekretaris Lain',
            'nis' => '1022003',
            'email' => 'sekretaris3@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
        $token = $secretary->createToken('test')->plainTextToken;
        $otherDeviceToken = 'fcm-token-owned-by-other-user';
        PushToken::create([
            'user_id' => $otherSecretary->id,
            'token' => $otherDeviceToken,
            'platform' => 'android',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/notifications/device-token', [
                'token' => 'fcm-token-for-secretary',
                'platform' => 'android',
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $secretary->id,
            'token' => 'fcm-token-for-secretary',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/notifications/device-token', [
                'token' => $otherDeviceToken,
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_tokens', ['token' => $otherDeviceToken]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/notifications/device-token', [
                'token' => 'fcm-token-for-secretary',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('push_tokens', ['token' => 'fcm-token-for-secretary']);
    }

    public function test_admin_reminder_uses_fcm_v1_and_a_silent_android_channel(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-fcm@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $secretary = User::create([
            'name' => 'Sekretaris',
            'nis' => '1022004',
            'email' => 'sekretaris4@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
        $reminder = AttendanceReminder::create([
            'user_id' => $secretary->id,
            'created_by' => $admin->id,
            'title' => 'Pengingat Penilaian Jam ke-1',
            'message' => 'Segera isi penilaian guru.',
        ]);
        PushToken::create([
            'user_id' => $secretary->id,
            'token' => 'fcm-device-token',
            'platform' => 'android',
        ]);

        config(['services.fcm.project_id' => 'school-test-project']);
        $this->mock(FcmAccessTokenProvider::class)
            ->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('test-access-token');
        Http::fake([
            'https://fcm.googleapis.com/v1/projects/school-test-project/messages:send' => Http::response([
                'name' => 'projects/school-test-project/messages/123',
            ]),
        ]);

        $sent = app(FcmMessagingService::class)->sendReminder($secretary, $reminder);

        $this->assertSame(1, $sent);
        Http::assertSent(function (Request $request): bool {
            $message = $request->data()['message'] ?? [];

            return $request->url() === 'https://fcm.googleapis.com/v1/projects/school-test-project/messages:send'
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && ($message['token'] ?? null) === 'fcm-device-token'
                && ($message['notification']['title'] ?? null) === 'Pengingat Penilaian Jam ke-1'
                && ($message['android']['notification']['channel_id'] ?? null) === 'reminders'
                && !array_key_exists('sound', $message['android']['notification'] ?? []);
        });
    }
}

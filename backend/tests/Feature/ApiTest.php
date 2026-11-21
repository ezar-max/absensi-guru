<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $secretary;
    protected User $inactiveSecretary;
    protected User $admin;
    protected SchoolClass $class;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('late_tolerance_min', 10);
        Setting::set('absent_threshold_pct', 50);
        Setting::set('edit_lock_min', 15);

        $this->class = SchoolClass::create(['name' => 'X-1', 'academic_year' => '2026/2027']);

        $this->secretary = User::create([
            'name' => 'Sekretaris Aktif',
            'nis' => '1022001',
            'email' => 'sekretaris@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
        $this->class->secretaries()->attach($this->secretary->id);

        $this->inactiveSecretary = User::create([
            'name' => 'Sekretaris Nonaktif',
            'email' => 'nonaktif@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'secretary',
            'is_active' => false,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $subject = Subject::create(['name' => 'Matematika']);
        $teacher = Teacher::create(['name' => 'Pak Guru', 'nip' => '123', 'is_active' => true]);
        $period = Period::create([
            'label' => 'Jam ke-1',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
        ]);

        // Schedule pada hari Jumat (5)
        $this->schedule = Schedule::create([
            'class_id' => $this->class->id,
            'day_of_week' => 5,
            'period_id' => $period->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_login_success_for_secretary(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'sekretaris@test.com',
            'password' => 'secret123',
            'device' => 'Samsung A52',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role'],
                'classes' => [['id', 'name']],
            ]);

        $this->assertEquals('X-1', $response->json('classes.0.name'));
    }

    public function test_login_success_with_username_or_nis_for_secretary(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => '1022001',
            'password' => 'secret123',
            'device' => 'Xiaomi Note',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'Sekretaris Aktif')
            ->assertJsonPath('user.nis', '1022001');
    }

    public function test_login_rejected_with_wrong_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'sekretaris@test.com',
            'password' => 'salah123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.message.0', 'NIS atau kata sandi salah.');
    }

    public function test_login_rejected_for_inactive_account(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonaktif@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.message.0', 'Akun Anda telah dinonaktifkan. Silakan hubungi admin.');
    }

    public function test_login_rejected_for_admin_role_on_mobile(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.message.0', 'Akun ini tidak memiliki akses ke aplikasi mobile.');
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Berhasil keluar.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->secretary->id,
        ]);
    }

    public function test_today_endpoint_returns_schedule_and_server_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:15:00')); // Jumat

        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/attendance/today');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_time',
                'holiday',
                'data' => [
                    [
                        'schedule_id',
                        'class',
                        'period',
                        'start',
                        'end',
                        'subject',
                        'teacher',
                        'attendance',
                    ]
                ]
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->schedule->id, $response->json('data.0.schedule_id'));
    }


    public function test_store_attendance_via_api(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00')); // Jumat

        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/attendance', [
                'schedule_id' => $this->schedule->id,
                'action' => 'datang',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Absensi berhasil dicatat.')
            ->assertJsonPath('data.status', 'hadir')
            ->assertJsonPath('data.late_minutes', 0);
    }

    public function test_history_endpoint_returns_paginated_list(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));

        $token = $this->secretary->createToken('test')->plainTextToken;

        // Catat absensi dulu
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/attendance', [
                'schedule_id' => $this->schedule->id,
                'action' => 'datang',
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/attendance/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    [
                        'id',
                        'date',
                        'status',
                        'class',
                        'subject',
                        'teacher',
                    ]
                ],
                'per_page',
                'total',
            ]);

        $this->assertEquals(1, $response->json('total'));
    }
}


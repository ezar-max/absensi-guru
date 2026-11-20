<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceService $service;
    protected User $admin;
    protected User $secretary1;
    protected User $secretary2;
    protected User $otherSecretary;
    protected SchoolClass $class;
    protected SchoolClass $otherClass;
    protected Teacher $teacher;
    protected Subject $subject;
    protected Period $period; // 07:00:00 - 09:00:00
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AttendanceService();

        // Settings
        Setting::set('late_tolerance_min', 10);
        Setting::set('absent_threshold_pct', 50);
        Setting::set('edit_lock_min', 15);

        // Users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->secretary1 = User::create([
            'name' => 'Sekretaris 1',
            'email' => 'sec1@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        $this->secretary2 = User::create([
            'name' => 'Sekretaris 2',
            'email' => 'sec2@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        $this->otherSecretary = User::create([
            'name' => 'Sekretaris Lain',
            'email' => 'sec_other@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        // Classes
        $this->class = SchoolClass::create([
            'name' => 'X IPA 1',
            'academic_year' => '2026/2027',
        ]);
        $this->class->secretaries()->attach([$this->secretary1->id, $this->secretary2->id]);

        $this->otherClass = SchoolClass::create([
            'name' => 'X IPA 2',
            'academic_year' => '2026/2027',
        ]);
        $this->otherClass->secretaries()->attach([$this->otherSecretary->id]);

        // Subject & Teacher
        $this->subject = Subject::create(['name' => 'Matematika']);
        $this->teacher = Teacher::create(['name' => 'Pak Ahmad', 'nip' => '123456', 'is_active' => true]);

        // Period 07:00:00 - 09:00:00 sesuai contoh baku di Bagian 6
        $this->period = Period::create([
            'label' => 'Jam ke-1-2',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ]);

        // Schedule di hari Jumat (day_of_week = 5)
        $this->schedule = Schedule::create([
            'class_id' => $this->class->id,
            'day_of_week' => 5, // Jumat
            'period_id' => $this->period->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Contoh baku baris 1: 07:05 -> Hadir, late 0
     */
    public function test_datang_at_0705_results_in_hadir_late_0(): void
    {
        // 2026-11-20 adalah hari Jumat (dayOfWeekIso = 5)
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));

        $attendance = $this->service->submit($this->secretary1, $this->schedule->id, 'datang');

        $this->assertEquals('hadir', $attendance->status);
        $this->assertEquals(0, $attendance->late_minutes);
        $this->assertEquals('07:05:00', $attendance->arrived_at);
        $this->assertEquals($this->secretary1->id, $attendance->recorded_by);
    }

    /**
     * Contoh baku baris 2: 07:10 -> Hadir, late 0 (batas toleransi)
     */
    public function test_datang_at_0710_results_in_hadir_late_0_tolerance_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:10:00'));

        $attendance = $this->service->submit($this->secretary1, $this->schedule->id, 'datang');

        $this->assertEquals('hadir', $attendance->status);
        $this->assertEquals(0, $attendance->late_minutes);
        $this->assertEquals('07:10:00', $attendance->arrived_at);
    }

    /**
     * Contoh baku baris 3: 07:30 -> Terlambat, late 30
     */
    public function test_datang_at_0730_results_in_terlambat_late_30(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:30:00'));

        $attendance = $this->service->submit($this->secretary1, $this->schedule->id, 'datang');

        $this->assertEquals('terlambat', $attendance->status);
        $this->assertEquals(30, $attendance->late_minutes);
        $this->assertEquals('07:30:00', $attendance->arrived_at);
    }

    /**
     * Contoh baku baris 4: kosong at 07:40 -> Ditolak (batas 50% = 08:00)
     */
    public function test_kosong_at_0740_is_rejected_for_secretary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:40:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->secretary1, $this->schedule->id, 'kosong');
    }

    /**
     * Admin boleh kosong sebelum batas 50%
     */
    public function test_kosong_at_0740_is_allowed_for_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:40:00'));

        $attendance = $this->service->submit($this->admin, $this->schedule->id, 'kosong');

        $this->assertEquals('kosong', $attendance->status);
        $this->assertNull($attendance->arrived_at);
        $this->assertEquals(0, $attendance->late_minutes);
    }

    /**
     * Contoh baku baris 5: kosong at 08:00 -> Kosong
     */
    public function test_kosong_at_0800_results_in_kosong(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00'));

        $attendance = $this->service->submit($this->secretary1, $this->schedule->id, 'kosong');

        $this->assertEquals('kosong', $attendance->status);
        $this->assertNull($attendance->arrived_at);
        $this->assertEquals(0, $attendance->late_minutes);
    }

    /**
     * Contoh baku baris 6: datang at 06:59 -> Ditolak, belum mulai
     */
    public function test_datang_at_0659_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 06:59:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->secretary1, $this->schedule->id, 'datang');
    }

    /**
     * Contoh baku baris 7: datang at 09:01 -> Ditolak untuk sekretaris, sudah selesai
     */
    public function test_datang_at_0901_is_rejected_for_secretary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 09:01:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->secretary1, $this->schedule->id, 'datang');
    }

    /**
     * Admin boleh mengabsen setelah jam selesai
     */
    public function test_datang_at_0901_is_allowed_for_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 09:01:00'));

        $attendance = $this->service->submit($this->admin, $this->schedule->id, 'datang');

        $this->assertEquals('terlambat', $attendance->status);
        $this->assertEquals(121, $attendance->late_minutes);
    }

    /**
     * Koreksi dalam jendela edit_lock_min (15 menit)
     */
    public function test_koreksi_within_edit_lock_window_is_allowed_and_preserves_first_arrival(): void
    {
        // Pertama catat terlambat pada 07:30
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:30:00'));
        $first = $this->service->submit($this->secretary1, $this->schedule->id, 'datang');
        $this->assertEquals('terlambat', $first->status);
        $this->assertEquals(30, $first->late_minutes);

        // 10 menit kemudian (07:40), koreksi menjadi izin
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:40:00'));
        $second = $this->service->submit($this->secretary1, $this->schedule->id, 'izin', 'Guru mendadak izin dinas');
        $this->assertEquals('izin', $second->status);
        $this->assertEquals('Guru mendadak izin dinas', $second->note);

        // Koreksi kembali menjadi datang: arrived_at dan late_minutes pertama harus dipertahankan!
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:42:00'));
        $third = $this->service->submit($this->secretary1, $this->schedule->id, 'datang');
        $this->assertEquals('terlambat', $third->status);
        $this->assertEquals(30, $third->late_minutes);
        $this->assertEquals('07:30:00', $third->arrived_at);
    }

    /**
     * Koreksi di luar jendela edit_lock_min untuk sekretaris ditolak
     */
    public function test_koreksi_outside_edit_lock_window_is_rejected_for_secretary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));
        $this->service->submit($this->secretary1, $this->schedule->id, 'datang');

        // 20 menit kemudian (>15 mnt)
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:25:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->secretary1, $this->schedule->id, 'izin');
    }

    /**
     * Koreksi di luar jendela edit_lock_min untuk admin selalu diperbolehkan
     */
    public function test_koreksi_outside_edit_lock_window_is_allowed_for_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));
        $this->service->submit($this->secretary1, $this->schedule->id, 'datang');

        // 60 menit kemudian
        Carbon::setTestNow(Carbon::parse('2026-11-20 08:05:00'));
        $corrected = $this->service->submit($this->admin, $this->schedule->id, 'izin', 'Koreksi oleh TU');

        $this->assertEquals('izin', $corrected->status);
        $this->assertEquals('Koreksi oleh TU', $corrected->note);
    }

    /**
     * Sekretaris kelas lain ditolak
     */
    public function test_secretary_from_another_class_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->otherSecretary, $this->schedule->id, 'datang');
    }

    /**
     * Hari bukan hari jadwal ditolak
     */
    public function test_wrong_day_of_week_is_rejected(): void
    {
        // 2026-11-21 adalah hari Sabtu (dayOfWeekIso = 6), sedangkan jadwal hari Jumat (5)
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:05:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->secretary1, $this->schedule->id, 'datang');
    }

    /**
     * Hari libur ditolak
     */
    public function test_holiday_is_rejected(): void
    {
        Holiday::create([
            'date' => '2026-11-20',
            'name' => 'Hari Guru Nasional',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));

        $this->expectException(ValidationException::class);
        $this->service->submit($this->secretary1, $this->schedule->id, 'datang');
    }

    /**
     * Audit log tercatat saat insert dan update
     */
    public function test_audit_log_is_recorded_on_insert_and_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 07:05:00'));
        $attendance = $this->service->submit($this->secretary1, $this->schedule->id, 'datang');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->secretary1->id,
            'action' => 'insert',
            'table_name' => 'attendances',
            'record_id' => $attendance->id,
        ]);

        // Koreksi
        $this->service->submit($this->secretary1, $this->schedule->id, 'izin', 'Alasan izin');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->secretary1->id,
            'action' => 'update',
            'table_name' => 'attendances',
            'record_id' => $attendance->id,
        ]);
    }
}


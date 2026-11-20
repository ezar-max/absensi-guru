<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ScheduleCollisionTest extends TestCase
{
    use RefreshDatabase;

    protected SchoolClass $classA;
    protected SchoolClass $classB;
    protected Teacher $teacherA;
    protected Teacher $teacherB;
    protected Subject $math;
    protected Subject $physics;
    protected Period $period1;
    protected Period $period2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classA = SchoolClass::create(['name' => 'X-1', 'academic_year' => '2026/2027']);
        $this->classB = SchoolClass::create(['name' => 'X-2', 'academic_year' => '2026/2027']);

        $this->teacherA = Teacher::create(['name' => 'Pak Guru A', 'nip' => '111', 'is_active' => true]);
        $this->teacherB = Teacher::create(['name' => 'Bu Guru B', 'nip' => '222', 'is_active' => true]);

        $this->math = Subject::create(['name' => 'Matematika']);
        $this->physics = Subject::create(['name' => 'Fisika']);

        $this->period1 = Period::create([
            'label' => 'Jam 1',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $this->period2 = Period::create([
            'label' => 'Jam 2',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);
    }

    /**
     * Guru bentrok: Guru A sudah mengajar di kelas lain pada slot yang sama
     */
    public function test_teacher_collision_is_rejected(): void
    {
        // Pak Guru A mengajar di Kelas A pada Senin (1) Jam 1
        Schedule::create([
            'class_id' => $this->classA->id,
            'day_of_week' => 1,
            'period_id' => $this->period1->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacherA->id,
        ]);

        // Coba jadwalkan Pak Guru A di Kelas B pada slot yang sama
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Guru {$this->teacherA->name} sudah mengajar di kelas {$this->classA->name} pada slot yang sama.");

        Schedule::create([
            'class_id' => $this->classB->id,
            'day_of_week' => 1,
            'period_id' => $this->period1->id,
            'subject_id' => $this->physics->id,
            'teacher_id' => $this->teacherA->id,
        ]);
    }

    /**
     * Kelas bentrok: Kelas A sudah punya mapel lain pada slot yang sama
     */
    public function test_class_collision_is_rejected(): void
    {
        // Kelas A sudah punya Matematika pada Senin Jam 1
        Schedule::create([
            'class_id' => $this->classA->id,
            'day_of_week' => 1,
            'period_id' => $this->period1->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacherA->id,
        ]);

        // Coba jadwalkan Fisika dengan Bu Guru B di Kelas A pada slot yang sama
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Kelas {$this->classA->name} sudah punya mapel {$this->math->name} pada slot yang sama.");

        Schedule::create([
            'class_id' => $this->classA->id,
            'day_of_week' => 1,
            'period_id' => $this->period1->id,
            'subject_id' => $this->physics->id,
            'teacher_id' => $this->teacherB->id,
        ]);
    }

    /**
     * Guru yang sama di slot berbeda diterima
     */
    public function test_same_teacher_in_different_slot_is_allowed(): void
    {
        $sched1 = Schedule::create([
            'class_id' => $this->classA->id,
            'day_of_week' => 1,
            'period_id' => $this->period1->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacherA->id,
        ]);

        $sched2 = Schedule::create([
            'class_id' => $this->classB->id,
            'day_of_week' => 1,
            'period_id' => $this->period2->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacherA->id,
        ]);

        $this->assertNotNull($sched1->id);
        $this->assertNotNull($sched2->id);
    }

    /**
     * Periode tumpang tindih ditolak
     */
    public function test_overlapping_period_is_rejected(): void
    {
        // Period1 adalah 07:00 - 08:00
        // Coba buat period overlap 07:30 - 08:30
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jam pelajaran tidak boleh tumpang tindih dengan jam pelajaran lain.');

        Period::create([
            'label' => 'Jam Overlap',
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
        ]);
    }

    /**
     * Periode dengan end_time <= start_time ditolak
     */
    public function test_period_with_invalid_times_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jam selesai harus lebih besar dari jam mulai.');

        Period::create([
            'label' => 'Jam Terbalik',
            'start_time' => '10:00:00',
            'end_time' => '09:00:00',
        ]);
    }
}


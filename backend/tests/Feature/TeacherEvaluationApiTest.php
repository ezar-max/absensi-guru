<?php

namespace Tests\Feature;

use App\Filament\Pages\AttendanceRecap;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherEvaluationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $secretary;
    protected SchoolClass $assignedClass;
    protected SchoolClass $otherClass;
    protected Period $period;
    protected Subject $math;
    protected Subject $physics;
    protected Teacher $mathTeacher;
    protected Teacher $physicsTeacher;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretary = User::create([
            'name' => 'Sekretaris',
            'nis' => '1022001',
            'email' => 'sekretaris@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        $this->assignedClass = SchoolClass::create([
            'name' => 'X-1',
            'academic_year' => '2026/2027',
        ]);
        $this->otherClass = SchoolClass::create([
            'name' => 'X-2',
            'academic_year' => '2026/2027',
        ]);
        $this->assignedClass->secretaries()->attach($this->secretary->id);

        $this->period = Period::create([
            'label' => 'Jam ke-1',
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
        ]);
        $this->math = Subject::create(['name' => 'Matematika']);
        $this->physics = Subject::create(['name' => 'Fisika']);

        $this->mathTeacher = Teacher::create([
            'name' => 'Guru Matematika',
            'is_active' => true,
        ]);
        $this->mathTeacher->subjects()->attach($this->math);

        $this->physicsTeacher = Teacher::create([
            'name' => 'Guru Fisika',
            'is_active' => true,
        ]);
        $this->physicsTeacher->subjects()->attach($this->physics);

        $this->schedule = Schedule::create([
            'class_id' => $this->assignedClass->id,
            'day_of_week' => 6,
            'period_id' => $this->period->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->mathTeacher->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_options_include_only_the_secretarys_classes_and_subject_teacher_mappings(): void
    {
        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/teacher-ratings/options');

        $response->assertOk()
            ->assertJsonPath('classes.0.id', $this->assignedClass->id)
            ->assertJsonPath('teachers.0.id', $this->physicsTeacher->id)
            ->assertJsonPath('ratings.0.value', 'sangat_baik')
            ->assertJsonPath('ratings.3.value', 'kurang');

        $this->assertSame(
            [$this->math->id],
            $response->json('teachers.1.subject_ids')
        );
    }

    public function test_secretary_can_submit_a_rating_without_an_optional_note(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00'));
        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', [
                'class_id' => $this->assignedClass->id,
                'period_id' => $this->period->id,
                'subject_id' => $this->math->id,
                'teacher_id' => $this->mathTeacher->id,
                'rating' => 'cukup',
                'teacher_present' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Penilaian guru berhasil disimpan.')
            ->assertJsonPath('data.rating', 'cukup')
            ->assertJsonPath('data.teacher_present', true)
            ->assertJsonPath('data.note', null);

        $evaluation = TeacherEvaluation::query()->whereDate('date', '2026-11-21')->firstOrFail();
        $this->assertSame(1, TeacherEvaluation::query()->whereDate('date', '2026-11-21')->count());
        $this->assertTrue($evaluation->teacher_present);
    }

    public function test_submitted_rating_is_returned_in_secretary_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00'));
        $token = $this->secretary->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', [
                'class_id' => $this->assignedClass->id,
                'period_id' => $this->period->id,
                'subject_id' => $this->math->id,
                'teacher_id' => $this->mathTeacher->id,
                'rating' => 'baik',
                'teacher_present' => true,
                'note' => 'Datang tepat waktu.',
            ])
            ->assertOk();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/teacher-ratings/history');

        $response->assertOk()
            ->assertJsonPath('data.0.type', 'penilaian')
            ->assertJsonPath('data.0.rating', 'baik')
            ->assertJsonPath('data.0.teacher_present', true)
            ->assertJsonPath('data.0.rating_label', 'Baik')
            ->assertJsonPath('data.0.note', 'Datang tepat waktu.');
    }

    public function test_secretary_cannot_submit_a_rating_after_the_period_ends(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:46:00', 'Asia/Jakarta'));
        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', [
                'class_id' => $this->assignedClass->id,
                'period_id' => $this->period->id,
                'subject_id' => $this->math->id,
                'teacher_id' => $this->mathTeacher->id,
                'rating' => 'baik',
                'teacher_present' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('message')
            ->assertJsonPath('errors.message.0', 'Jam pelajaran sudah lewat. Penilaian tidak dapat diisi lagi.');
    }

    public function test_secretary_cannot_rate_a_class_they_are_not_assigned_to(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00', 'Asia/Jakarta'));
        $token = $this->secretary->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', [
                'class_id' => $this->otherClass->id,
                'period_id' => $this->period->id,
                'subject_id' => $this->math->id,
                'teacher_id' => $this->mathTeacher->id,
                'rating' => 'baik',
                'teacher_present' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('message')
            ->assertJsonPath('errors.message.0', 'Anda tidak memiliki akses untuk menilai kelas ini.');
    }

    public function test_today_schedules_include_rating_completion_and_deadline_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00', 'Asia/Jakarta'));
        $token = $this->secretary->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/teacher-ratings/today')
            ->assertOk()
            ->assertJsonPath('data.0.schedule_id', $this->schedule->id)
            ->assertJsonPath('data.0.class', 'X-1')
            ->assertJsonPath('data.0.can_rate', true)
            ->assertJsonPath('active_period_id', $this->period->id)
            ->assertJsonPath('data.0.rating', null);

        TeacherEvaluation::create([
            'class_id' => $this->assignedClass->id,
            'period_id' => $this->period->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->mathTeacher->id,
            'date' => '2026-11-21',
            'rating' => 'baik',
            'teacher_present' => true,
            'recorded_by' => $this->secretary->id,
        ]);
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/teacher-ratings/today')
            ->assertOk()
            ->assertJsonPath('data.0.rating', 'baik')
            ->assertJsonPath('data.0.teacher_present', true)
            ->assertJsonPath('data.0.rating_label', 'Baik');
    }

    public function test_manual_rating_is_returned_even_without_a_schedule_record(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00', 'Asia/Jakarta'));
        $this->schedule->delete();
        $token = $this->secretary->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', [
                'class_id' => $this->assignedClass->id,
                'period_id' => $this->period->id,
                'subject_id' => $this->math->id,
                'teacher_id' => $this->mathTeacher->id,
                'rating' => 'baik',
                'teacher_present' => true,
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/teacher-ratings/today')
            ->assertOk()
            ->assertJsonPath('data.0.schedule_id', null)
            ->assertJsonPath('data.0.class', 'X-1')
            ->assertJsonPath('data.0.rating', 'baik');
    }

    public function test_secretary_cannot_rate_a_teacher_for_an_unrelated_subject(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00', 'Asia/Jakarta'));
        $token = $this->secretary->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', [
                'class_id' => $this->assignedClass->id,
                'period_id' => $this->period->id,
                'subject_id' => $this->math->id,
                'teacher_id' => $this->physicsTeacher->id,
                'rating' => 'baik',
                'teacher_present' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message')
            ->assertJsonPath('errors.message.0', 'Guru tidak aktif atau tidak mengajar mapel yang dipilih.');
    }

    public function test_teacher_presence_is_required_and_can_be_marked_absent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 07:30:00', 'Asia/Jakarta'));
        $token = $this->secretary->createToken('test')->plainTextToken;
        $payload = [
            'class_id' => $this->assignedClass->id,
            'period_id' => $this->period->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->mathTeacher->id,
            'rating' => 'kurang',
        ];

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('teacher_present');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/teacher-ratings', $payload + ['teacher_present' => false]);

        $response->assertOk()
            ->assertJsonPath('data.teacher_present', false);
        $this->assertFalse(TeacherEvaluation::query()->firstOrFail()->teacher_present);
    }

    public function test_admin_recap_combines_presence_rating_and_note_in_one_row(): void
    {
        TeacherEvaluation::create([
            'class_id' => $this->assignedClass->id,
            'period_id' => $this->period->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->mathTeacher->id,
            'date' => '2026-11-21',
            'rating' => 'kurang',
            'teacher_present' => false,
            'note' => 'Guru tidak hadir.',
            'recorded_by' => $this->secretary->id,
        ]);
        TeacherEvaluation::create([
            'class_id' => $this->otherClass->id,
            'period_id' => $this->period->id,
            'subject_id' => $this->physics->id,
            'teacher_id' => $this->physicsTeacher->id,
            'date' => '2026-11-21',
            'rating' => 'baik',
            'teacher_present' => true,
            'recorded_by' => $this->secretary->id,
        ]);

        $page = new AttendanceRecap();
        $page->month = 11;
        $page->year = 2026;
        $data = $page->getViewData();
        $absentRow = $data['rows']->firstWhere('note', 'Guru tidak hadir.');

        $this->assertCount(2, $data['rows']);
        $this->assertSame('Guru tidak masuk', $absentRow['teacher_presence']);
        $this->assertSame('Guru tidak hadir.', $absentRow['note']);
        $this->assertSame(2, $data['totalSubmissions']);
        $this->assertSame(1, $data['teacherAbsentCount']);
        $this->assertCount(1, $data['attentionTeachers']);
        $this->assertSame('Guru Matematika', $data['attentionTeachers']->first()['teacher']);
        $this->assertSame(1, $data['attentionTeachers']->first()['rating_counts']['kurang']);
        $this->assertSame(1, $data['totalLowRatings']);

        $page->showDetails($absentRow['id']);
        $this->assertSame('Guru tidak hadir.', $page->getViewData()['selectedEvaluation']->note);
    }
}

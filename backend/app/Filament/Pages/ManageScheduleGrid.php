<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use InvalidArgumentException;
use Throwable;

class ManageScheduleGrid extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Jadwal';
    protected static ?string $navigationLabel = 'Grid Jadwal';
    protected static ?string $title = 'Penyusunan Grid Jadwal Kelas';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.manage-schedule-grid';

    public ?int $selectedClassId = null;

    // State untuk Modal Tambah/Edit Jadwal
    public bool $isModalOpen = false;
    public ?int $editingScheduleId = null;
    public ?int $modalDayOfWeek = null;
    public ?int $modalPeriodId = null;
    public ?int $modalSubjectId = null;
    public ?int $modalTeacherId = null;

    public array $days = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    public function mount(): void
    {
        $firstClass = SchoolClass::first();
        if ($firstClass) {
            $this->selectedClassId = $firstClass->id;
        }
    }

    public function updatedSelectedClassId(): void
    {
        // Re-render ketika kelas berubah
    }

    public function openCreateSlot(int $dayOfWeek, int $periodId): void
    {
        $this->editingScheduleId = null;
        $this->modalDayOfWeek = $dayOfWeek;
        $this->modalPeriodId = $periodId;
        $this->modalSubjectId = null;
        $this->modalTeacherId = null;
        $this->isModalOpen = true;
    }

    public function openEditSlot(int $scheduleId): void
    {
        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return;
        }

        $this->editingScheduleId = $schedule->id;
        $this->modalDayOfWeek = $schedule->day_of_week;
        $this->modalPeriodId = $schedule->period_id;
        $this->modalSubjectId = $schedule->subject_id;
        $this->modalTeacherId = $schedule->teacher_id;
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->editingScheduleId = null;
        $this->modalDayOfWeek = null;
        $this->modalPeriodId = null;
        $this->modalSubjectId = null;
        $this->modalTeacherId = null;
    }

    public function saveSlot(): void
    {
        $this->validate([
            'selectedClassId' => 'required|exists:classes,id',
            'modalDayOfWeek' => 'required|integer|between:1,6',
            'modalPeriodId' => 'required|exists:periods,id',
            'modalSubjectId' => 'required|exists:subjects,id',
            'modalTeacherId' => 'required|exists:teachers,id',
        ], [
            'modalSubjectId.required' => 'Mata pelajaran wajib dipilih.',
            'modalTeacherId.required' => 'Guru pengampu wajib dipilih.',
        ]);

        try {
            if ($this->editingScheduleId) {
                $schedule = Schedule::findOrFail($this->editingScheduleId);
                $schedule->update([
                    'class_id' => $this->selectedClassId,
                    'day_of_week' => $this->modalDayOfWeek,
                    'period_id' => $this->modalPeriodId,
                    'subject_id' => $this->modalSubjectId,
                    'teacher_id' => $this->modalTeacherId,
                ]);

                Notification::make()
                    ->title('Jadwal berhasil diperbarui')
                    ->success()
                    ->send();
            } else {
                Schedule::create([
                    'class_id' => $this->selectedClassId,
                    'day_of_week' => $this->modalDayOfWeek,
                    'period_id' => $this->modalPeriodId,
                    'subject_id' => $this->modalSubjectId,
                    'teacher_id' => $this->modalTeacherId,
                ]);

                Notification::make()
                    ->title('Jadwal berhasil ditambahkan')
                    ->success()
                    ->send();
            }

            $this->closeModal();
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title('Bentrok Jadwal Terdeteksi!')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Gagal Menyimpan Jadwal')
                ->body('Terjadi kesalahan atau duplikasi data jadwal: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteSlot(int $scheduleId): void
    {
        $schedule = Schedule::find($scheduleId);
        if ($schedule) {
            $schedule->delete();
            Notification::make()
                ->title('Jadwal berhasil dihapus')
                ->success()
                ->send();
        }
    }

    /**
     * Dapatkan daftar guru dengan status bentrok untuk slot yang sedang dipilih
     */
    public function getTeacherOptionsProperty(): array
    {
        if (!$this->modalDayOfWeek || !$this->modalPeriodId) {
            return [];
        }

        $teachers = Teacher::where('is_active', true)->orderBy('name')->get();
        $options = [];

        foreach ($teachers as $teacher) {
            // Cek apakah guru ini sudah mengajar di kelas lain pada slot ini
            $conflict = Schedule::where('teacher_id', $teacher->id)
                ->where('day_of_week', $this->modalDayOfWeek)
                ->where('period_id', $this->modalPeriodId)
                ->when($this->editingScheduleId, fn ($q) => $q->where('id', '!=', $this->editingScheduleId))
                ->with('schoolClass')
                ->first();

            if ($conflict) {
                $className = $conflict->schoolClass?->name ?? 'Kelas Lain';
                $options[] = [
                    'id' => $teacher->id,
                    'name' => "{$teacher->name} — [BENTROK: Mengajar di {$className}]",
                    'is_conflict' => true,
                ];
            } else {
                $options[] = [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'is_conflict' => false,
                ];
            }
        }

        return $options;
    }

    public function getViewData(): array
    {
        $classes = SchoolClass::orderBy('name')->get();
        $periods = Period::orderBy('start_time')->get();

        $scheduleMatrix = [];
        $totalClassSlots = 0;
        $uniqueTeachers = 0;
        $uniqueSubjects = 0;
        $selectedClass = null;

        if ($this->selectedClassId) {
            $selectedClass = $classes->firstWhere('id', $this->selectedClassId);
            $schedules = Schedule::where('class_id', $this->selectedClassId)
                ->with(['subject', 'teacher'])
                ->get();

            $totalClassSlots = $schedules->count();
            $uniqueTeachers = $schedules->pluck('teacher_id')->unique()->count();
            $uniqueSubjects = $schedules->pluck('subject_id')->unique()->count();

            foreach ($schedules as $s) {
                $scheduleMatrix[$s->period_id][$s->day_of_week] = $s;
            }
        }

        return [
            'classes' => $classes,
            'periods' => $periods,
            'scheduleMatrix' => $scheduleMatrix,
            'selectedClass' => $selectedClass,
            'totalClassSlots' => $totalClassSlots,
            'uniqueTeachers' => $uniqueTeachers,
            'uniqueSubjects' => $uniqueSubjects,
        ];
    }
}


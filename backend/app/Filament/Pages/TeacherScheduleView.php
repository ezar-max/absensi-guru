<?php

namespace App\Filament\Pages;

use App\Models\Period;
use App\Models\Schedule;
use App\Models\Teacher;
use Filament\Pages\Page;

class TeacherScheduleView extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Jadwal';
    protected static ?string $navigationLabel = 'Jadwal per Guru';
    protected static ?string $title = 'Jadwal Mengajar per Guru';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.teacher-schedule-view';

    public ?int $selectedTeacherId = null;

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
        $firstTeacher = Teacher::where('is_active', true)->first();
        if ($firstTeacher) {
            $this->selectedTeacherId = $firstTeacher->id;
        }
    }

    public function getViewData(): array
    {
        $teachers = Teacher::orderBy('name')->get();
        $periods = Period::orderBy('start_time')->get();

        $scheduleMatrix = [];
        $totalHours = 0;

        $selectedTeacher = null;

        if ($this->selectedTeacherId) {
            $selectedTeacher = $teachers->firstWhere('id', $this->selectedTeacherId);
            $schedules = Schedule::where('teacher_id', $this->selectedTeacherId)
                ->with(['subject', 'schoolClass', 'period'])
                ->get();

            $totalHours = $schedules->count();

            foreach ($schedules as $s) {
                $scheduleMatrix[$s->period_id][$s->day_of_week] = $s;
            }
        }

        return [
            'teachers' => $teachers,
            'selectedTeacher' => $selectedTeacher,
            'periods' => $periods,
            'scheduleMatrix' => $scheduleMatrix,
            'totalHours' => $totalHours,
        ];
    }
}


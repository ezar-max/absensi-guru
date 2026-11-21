<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherEvaluationController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSecretary(), 403);

        $now = Carbon::now(config('app.timezone'));
        $today = $now->toDateString();
        $classIds = $user->classes()->pluck('classes.id');
        $holiday = Holiday::whereDate('date', $today)->first();

        $schedules = Schedule::query()
            ->whereIn('class_id', $classIds)
            ->where('day_of_week', $now->dayOfWeekIso)
            ->with([
                'schoolClass:id,name',
                'period:id,label,start_time,end_time',
                'subject:id,name',
                'teacher:id,name',
            ])
            ->get()
            ->sortBy(fn (Schedule $schedule) => $schedule->period->start_time)
            ->values();

        $evaluations = TeacherEvaluation::query()
            ->with(['schoolClass:id,name', 'period:id,label,start_time,end_time', 'subject:id,name', 'teacher:id,name'])
            ->whereIn('class_id', $classIds)
            ->whereDate('date', $today)
            ->where('recorded_by', $user->id)
            ->get()
            ->keyBy(fn (TeacherEvaluation $evaluation) => "{$evaluation->class_id}:{$evaluation->period_id}");

        $ratingLabels = collect(TeacherEvaluation::RATINGS)->pluck('label', 'value');
        $scheduledSlots = $schedules->map(fn (Schedule $schedule) => "{$schedule->class_id}:{$schedule->period_id}");
        $scheduledData = $schedules->map(function (Schedule $schedule) use ($now, $holiday, $today, $evaluations, $ratingLabels) {
            $start = Carbon::parse($today . ' ' . $schedule->period->start_time, config('app.timezone'));
            $end = Carbon::parse($today . ' ' . $schedule->period->end_time, config('app.timezone'));
            $evaluation = $evaluations->get("{$schedule->class_id}:{$schedule->period_id}");

            return [
                'schedule_id' => $schedule->id,
                'evaluation_id' => $evaluation?->id,
                'class_id' => $schedule->class_id,
                'period_id' => $schedule->period_id,
                'subject_id' => $evaluation?->subject_id ?? $schedule->subject_id,
                'teacher_id' => $evaluation?->teacher_id ?? $schedule->teacher_id,
                'class' => $schedule->schoolClass->name,
                'period' => $schedule->period->label,
                'start' => substr($schedule->period->start_time, 0, 5),
                'end' => substr($schedule->period->end_time, 0, 5),
                'subject' => $evaluation?->subject?->name ?? $schedule->subject->name,
                'teacher' => $evaluation?->teacher?->name ?? $schedule->teacher->name,
                'rating' => $evaluation?->rating,
                'rating_label' => $evaluation ? ($ratingLabels[$evaluation->rating] ?? $evaluation->rating) : null,
                'teacher_present' => $evaluation?->teacher_present,
                'note' => $evaluation?->note,
                'created_at' => $evaluation?->created_at?->toIso8601String(),
                'can_rate' => !$holiday && $now->betweenIncluded($start, $end),
                'is_upcoming' => $now->lt($start),
                'is_expired' => $now->gt($end),
            ];
        });
        $manualData = $evaluations
            ->reject(fn (TeacherEvaluation $evaluation) => $scheduledSlots->contains("{$evaluation->class_id}:{$evaluation->period_id}"))
            ->map(fn (TeacherEvaluation $evaluation) => [
                'schedule_id' => null,
                'evaluation_id' => $evaluation->id,
                'class_id' => $evaluation->class_id,
                'period_id' => $evaluation->period_id,
                'subject_id' => $evaluation->subject_id,
                'teacher_id' => $evaluation->teacher_id,
                'class' => $evaluation->schoolClass->name,
                'period' => $evaluation->period->label,
                'start' => substr($evaluation->period->start_time, 0, 5),
                'end' => substr($evaluation->period->end_time, 0, 5),
                'subject' => $evaluation->subject->name,
                'teacher' => $evaluation->teacher->name,
                'rating' => $evaluation->rating,
                'rating_label' => $ratingLabels[$evaluation->rating] ?? $evaluation->rating,
                'teacher_present' => $evaluation->teacher_present,
                'note' => $evaluation->note,
                'created_at' => $evaluation->created_at?->toIso8601String(),
                'can_rate' => false,
                'is_upcoming' => false,
                'is_expired' => true,
            ]);
        $activePeriod = $holiday ? null : Period::query()
            ->orderBy('start_time')
            ->get()
            ->first(function (Period $period) use ($now, $today) {
                $start = Carbon::parse($today . ' ' . $period->start_time, config('app.timezone'));
                $end = Carbon::parse($today . ' ' . $period->end_time, config('app.timezone'));

                return $now->betweenIncluded($start, $end);
            });

        return response()->json([
            'server_time' => $now->toIso8601String(),
            'date' => $today,
            'active_period_id' => $activePeriod?->id,
            'holiday' => $holiday ? ['name' => $holiday->name, 'date' => $today] : null,
            'ratings' => TeacherEvaluation::RATINGS,
            'data' => $scheduledData->concat($manualData)->sortBy('start')->values(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSecretary(), 403);

        $classIds = $user->classes()->pluck('classes.id');

        $paginator = TeacherEvaluation::query()
            ->with([
                'schoolClass:id,name',
                'period:id,label,start_time,end_time',
                'subject:id,name',
                'teacher:id,name',
                'recordedBy:id,name',
            ])
            ->whereIn('class_id', $classIds)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30);

        $ratings = collect(TeacherEvaluation::RATINGS)->mapWithKeys(
            fn ($rating) => [$rating['value'] => $rating['label']]
        );

        $items = collect($paginator->items())->map(function (TeacherEvaluation $evaluation) use ($ratings) {
            return [
                'id' => $evaluation->id,
                'date' => $evaluation->date->toDateString(),
                'type' => 'penilaian',
                'rating' => $evaluation->rating,
                'rating_label' => $ratings[$evaluation->rating] ?? $evaluation->rating,
                'teacher_present' => $evaluation->teacher_present,
                'note' => $evaluation->note,
                'class' => $evaluation->schoolClass?->name ?? '',
                'period' => $evaluation->period?->label ?? '',
                'start' => $evaluation->period ? substr($evaluation->period->start_time, 0, 5) : '',
                'end' => $evaluation->period ? substr($evaluation->period->end_time, 0, 5) : '',
                'subject' => $evaluation->subject?->name ?? '',
                'teacher' => $evaluation->teacher?->name ?? '',
                'recorded_by' => $evaluation->recordedBy?->name ?? '',
            ];
        });

        return response()->json([
            'current_page' => $paginator->currentPage(),
            'data' => $items,
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSecretary(), 403);

        $teachers = Teacher::query()
            ->where('is_active', true)
            ->with('subjects:id')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Teacher $teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'subject_ids' => $teacher->subjects->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ]);

        return response()->json([
            'classes' => $user->classes()
                ->orderBy('name')
                ->get(['classes.id', 'classes.name', 'classes.major'])
                ->map(function ($schoolClass) {
                    return [
                        'id' => $schoolClass->id,
                        'name' => $schoolClass->name,
                        'major' => $schoolClass->major ?: '',
                    ];
                }),
            'periods' => Period::query()
                ->orderBy('start_time')
                ->get(['id', 'label', 'start_time', 'end_time'])
                ->map(fn (Period $period) => [
                    'id' => $period->id,
                    'label' => $period->label,
                    'start' => substr($period->start_time, 0, 5),
                    'end' => substr($period->end_time, 0, 5),
                ]),
            'subjects' => Subject::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Subject $subject) => ['id' => $subject->id, 'name' => $subject->name]),
            'teachers' => $teachers,
            'ratings' => TeacherEvaluation::RATINGS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSecretary(), 403);

        $validated = $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'period_id' => 'required|integer|exists:periods,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'teacher_id' => 'required|integer|exists:teachers,id',
            'rating' => ['required', Rule::in(array_column(TeacherEvaluation::RATINGS, 'value'))],
            'teacher_present' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ], [
            'rating.in' => 'Kriteria penilaian tidak valid.',
        ]);

        $now = Carbon::now(config('app.timezone'));
        $date = $now->toDateString();

        if (!$user->classes()->where('classes.id', $validated['class_id'])->exists()) {
            throw ValidationException::withMessages([
                'message' => ['Anda tidak memiliki akses untuk menilai kelas ini.'],
            ]);
        }

        $teacher = Teacher::query()
            ->where('is_active', true)
            ->whereHas('subjects', fn ($query) => $query->where('subjects.id', $validated['subject_id']))
            ->find($validated['teacher_id']);

        if (!$teacher) {
            throw ValidationException::withMessages([
                'message' => ['Guru tidak aktif atau tidak mengajar mapel yang dipilih.'],
            ]);
        }

        $holiday = Holiday::whereDate('date', $date)->first();
        if ($holiday) {
            throw ValidationException::withMessages([
                'message' => ["Hari ini libur: {$holiday->name}."],
            ]);
        }

        $period = Period::findOrFail($validated['period_id']);
        $start = Carbon::parse($date . ' ' . $period->start_time, config('app.timezone'));
        $end = Carbon::parse($date . ' ' . $period->end_time, config('app.timezone'));

        if ($now->lt($start)) {
            throw ValidationException::withMessages([
                'message' => ['Penilaian hanya dapat diisi saat jam pelajaran berlangsung.'],
            ]);
        }

        if ($now->gt($end)) {
            throw ValidationException::withMessages([
                'message' => ['Jam pelajaran sudah lewat. Penilaian tidak dapat diisi lagi.'],
            ]);
        }

        $attributes = [
            'class_id' => $validated['class_id'],
            'period_id' => $validated['period_id'],
            'date' => $date,
            'recorded_by' => $user->id,
        ];
        $values = [
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $validated['teacher_id'],
            'rating' => $validated['rating'],
            'teacher_present' => $validated['teacher_present'],
            'note' => $validated['note'] ?? null,
        ];

        $evaluation = DB::transaction(function () use ($attributes, $values) {
            $existing = TeacherEvaluation::query()
                ->where('class_id', $attributes['class_id'])
                ->where('period_id', $attributes['period_id'])
                ->whereDate('date', $attributes['date'])
                ->where('recorded_by', $attributes['recorded_by'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->update($values);

                return $existing->fresh();
            }

            return TeacherEvaluation::create($attributes + $values);
        });

        return response()->json([
            'message' => 'Penilaian guru berhasil disimpan.',
            'data' => [
                'id' => $evaluation->id,
                'class_id' => $evaluation->class_id,
                'period_id' => $evaluation->period_id,
                'subject_id' => $evaluation->subject_id,
                'teacher_id' => $evaluation->teacher_id,
                'date' => $evaluation->date->toDateString(),
                'rating' => $evaluation->rating,
                'teacher_present' => $evaluation->teacher_present,
                'note' => $evaluation->note,
            ],
        ]);
    }
}

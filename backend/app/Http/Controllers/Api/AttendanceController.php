<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Get today's schedules and attendance for secretary's classes.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now();
        $todayDate = $now->toDateString();
        $todayDayOfWeek = $now->dayOfWeekIso; // 1=Senin..7=Minggu

        $holiday = Holiday::where('date', $todayDate)->first();

        // Ambil ID kelas milik sekretaris (admin dapat melihat semua kelas bila mengakses API)
        $classQuery = $user->classes();
        if ($user->isAdmin()) {
            $classIds = \App\Models\SchoolClass::pluck('id')->toArray();
        } else {
            $classIds = $classQuery->pluck('classes.id')->toArray();
        }

        $schedules = Schedule::whereIn('class_id', $classIds)
            ->where('day_of_week', $todayDayOfWeek)
            ->with([
                'schoolClass:id,name',
                'period:id,label,start_time,end_time',
                'subject:id,name',
                'teacher:id,name',
                'attendances' => function ($q) use ($todayDate) {
                    $q->whereDate('date', $todayDate);
                },
            ])
            ->join('periods', 'schedules.period_id', '=', 'periods.id')
            ->orderBy('periods.start_time')
            ->select('schedules.*')
            ->get();

        $data = $schedules->map(function (Schedule $schedule) {
            $att = $schedule->attendances->first();

            $attendanceData = null;
            if ($att) {
                $attendanceData = [
                    'id' => $att->id,
                    'status' => $att->status,
                    'arrived_at' => $att->arrived_at,
                    'late_minutes' => $att->late_minutes,
                    'note' => $att->note,
                    'created_at' => $att->created_at?->toIso8601String(),
                ];
            }

            // Format jam "07:00:00" -> "07:00"
            $startTime = substr($schedule->period->start_time, 0, 5);
            $endTime = substr($schedule->period->end_time, 0, 5);

            return [
                'schedule_id' => $schedule->id,
                'class' => $schedule->schoolClass?->name ?? '',
                'period' => $schedule->period?->label ?? '',
                'start' => $startTime,
                'end' => $endTime,
                'subject' => $schedule->subject?->name ?? '',
                'teacher' => $schedule->teacher?->name ?? '',
                'attendance' => $attendanceData,
            ];
        });

        return response()->json([
            'server_time' => $now->toIso8601String(),
            'holiday' => $holiday ? ['name' => $holiday->name, 'date' => $holiday->date] : null,
            'data' => $data,
        ]);
    }

    /**
     * Store or correct attendance.
     */
    public function store(Request $request, AttendanceService $service): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|integer|exists:schedules,id',
            'action' => 'required|string|in:datang,kosong,izin,sakit,dinas',
            'note' => 'nullable|string|max:500',
        ], [
            'schedule_id.required' => 'ID Jadwal wajib disertakan.',
            'schedule_id.exists' => 'Jadwal tidak ditemukan.',
            'action.required' => 'Aksi absensi wajib dipilih.',
            'action.in' => 'Aksi absensi tidak valid.',
        ]);

        $attendance = $service->submit(
            user: $request->user(),
            scheduleId: (int) $validated['schedule_id'],
            action: $validated['action'],
            note: $validated['note'] ?? null
        );

        return response()->json([
            'message' => 'Absensi berhasil dicatat.',
            'data' => $attendance,
        ], 200);
    }

    /**
     * Get attendance history for secretary's classes.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $classIds = \App\Models\SchoolClass::pluck('id')->toArray();
        } else {
            $classIds = $user->classes()->pluck('classes.id')->toArray();
        }

        $query = Attendance::whereHas('schedule', function ($q) use ($classIds) {
            $q->whereIn('class_id', $classIds);
        })->with([
            'schedule.schoolClass:id,name',
            'schedule.period:id,label,start_time,end_time',
            'schedule.subject:id,name',
            'schedule.teacher:id,name',
            'recordedBy:id,name',
        ]);

        // Filter status opsional (mis. hadir, terlambat, kosong)
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Urutkan tanggal terbaru dulu, lalu jam periode terbaru
        $paginator = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30);

        $items = collect($paginator->items())->map(function (Attendance $att) {
            $schedule = $att->schedule;
            $startTime = $schedule?->period ? substr($schedule->period->start_time, 0, 5) : '';
            $endTime = $schedule?->period ? substr($schedule->period->end_time, 0, 5) : '';

            return [
                'id' => $att->id,
                'date' => $att->date->toDateString(),
                'status' => $att->status,
                'arrived_at' => $att->arrived_at,
                'late_minutes' => $att->late_minutes,
                'note' => $att->note,
                'created_at' => $att->created_at?->toIso8601String(),
                'class' => $schedule?->schoolClass?->name ?? '',
                'period' => $schedule?->period?->label ?? '',
                'start' => $startTime,
                'end' => $endTime,
                'subject' => $schedule?->subject?->name ?? '',
                'teacher' => $schedule?->teacher?->name ?? '',
                'recorded_by' => $att->recordedBy?->name ?? '',
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
}


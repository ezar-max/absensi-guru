<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Submit attendance for a schedule.
     *
     * @param User $user
     * @param int $scheduleId
     * @param string $action ('datang'|'kosong'|'izin'|'sakit'|'dinas')
     * @param string|null $note
     * @return Attendance
     * @throws ValidationException
     */
    public function submit(User $user, int $scheduleId, string $action, ?string $note = null): Attendance
    {
        $validActions = ['datang', 'kosong', 'izin', 'sakit', 'dinas'];
        if (!in_array($action, $validActions)) {
            throw ValidationException::withMessages([
                'message' => ['Aksi absensi tidak valid.'],
            ]);
        }

        $now = Carbon::now();
        $todayDate = $now->toDateString();
        $todayDayOfWeek = $now->dayOfWeekIso; // 1=Senin..7=Minggu

        $schedule = Schedule::with(['schoolClass', 'period', 'teacher', 'subject'])->find($scheduleId);
        if (!$schedule) {
            throw ValidationException::withMessages([
                'message' => ['Jadwal tidak ditemukan.'],
            ]);
        }

        // 1. Sekretaris harus terikat ke kelas pada jadwal itu (admin bebas)
        if (!$user->isAdmin()) {
            $hasAccess = $user->classes()->where('classes.id', $schedule->class_id)->exists();
            if (!$hasAccess) {
                throw ValidationException::withMessages([
                    'message' => ['Anda tidak memiliki akses untuk mengabsen kelas ini.'],
                ]);
            }
        }

        // 2. day_of_week jadwal harus sama dengan hari ini
        if ($schedule->day_of_week !== $todayDayOfWeek) {
            throw ValidationException::withMessages([
                'message' => ['Jadwal ini bukan untuk hari ini.'],
            ]);
        }

        // 3. Hari ini bukan hari libur (holidays)
        $holiday = Holiday::where('date', $todayDate)->first();
        if ($holiday) {
            throw ValidationException::withMessages([
                'message' => ["Hari ini libur: {$holiday->name}."],
            ]);
        }

        $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $todayDate . ' ' . $schedule->period->start_time);
        $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $todayDate . ' ' . $schedule->period->end_time);

        // 4. Sekarang >= start_time periode ("Jam pelajaran belum dimulai")
        if ($now->lt($startTime)) {
            throw ValidationException::withMessages([
                'message' => ['Jam pelajaran belum dimulai.'],
            ]);
        }

        // 5. Sekarang <= end_time periode untuk sekretaris ("Jam pelajaran sudah selesai, hubungi admin"); admin boleh melewati
        if (!$user->isAdmin() && $now->gt($endTime)) {
            throw ValidationException::withMessages([
                'message' => ['Jam pelajaran sudah selesai, hubungi admin.'],
            ]);
        }

        $lateToleranceMin = Setting::getInt('late_tolerance_min', 10);
        $absentThresholdPct = Setting::getInt('absent_threshold_pct', 50);
        $editLockMin = Setting::getInt('edit_lock_min', 15);

        // Durasi periode
        $durationMinutes = $startTime->diffInMinutes($endTime);
        $minutesSinceStart = $startTime->diffInMinutes($now);

        try {
            return DB::transaction(function () use (
                $user,
                $schedule,
                $action,
                $note,
                $now,
                $todayDate,
                $startTime,
                $durationMinutes,
                $minutesSinceStart,
                $lateToleranceMin,
                $absentThresholdPct,
                $editLockMin
            ) {
                $attendance = Attendance::where('schedule_id', $schedule->id)
                    ->where('date', $todayDate)
                    ->lockForUpdate()
                    ->first();

                $isCorrection = ($attendance !== null);

                // Validasi batas waktu koreksi untuk sekretaris
                if ($isCorrection && !$user->isAdmin()) {
                    $minutesSinceCreated = $attendance->created_at->diffInMinutes($now);
                    if ($minutesSinceCreated > $editLockMin) {
                        throw ValidationException::withMessages([
                            'message' => ['Batas waktu koreksi habis, hubungi admin.'],
                        ]);
                    }
                }

                // Penentuan status
                $status = null;
                $arrivedAt = null;
                $lateMinutes = 0;

                if ($action === 'datang') {
                    $firstArrival = null;
                    if ($isCorrection) {
                        if ($attendance->arrived_at !== null) {
                            $firstArrival = [
                                'arrived_at' => $attendance->arrived_at,
                                'late_minutes' => $attendance->late_minutes,
                            ];
                        } else {
                            $log = AuditLog::where('table_name', 'attendances')
                                ->where('record_id', (string) $attendance->id)
                                ->get()
                                ->first(function ($l) {
                                    $detail = $l->detail ?? [];
                                    $arrived = $detail['arrived_at'] ?? $detail['after']['arrived_at'] ?? null;
                                    return !empty($arrived);
                                });

                            if ($log) {
                                $detail = $log->detail ?? [];
                                $firstArrival = [
                                    'arrived_at' => $detail['arrived_at'] ?? $detail['after']['arrived_at'],
                                    'late_minutes' => $detail['late_minutes'] ?? $detail['after']['late_minutes'] ?? 0,
                                ];
                            }
                        }
                    }

                    if ($firstArrival !== null) {
                        // Pertahankan arrived_at dan late_minutes pertama saat koreksi
                        $arrivedAt = $firstArrival['arrived_at'];
                        $lateMinutes = (int) $firstArrival['late_minutes'];
                        $status = ($lateMinutes > 0) ? 'terlambat' : 'hadir';
                    } else {
                        $arrivedAt = $now->format('H:i:s');
                        if ($minutesSinceStart <= $lateToleranceMin) {
                            $status = 'hadir';
                            $lateMinutes = 0;
                        } else {
                            $status = 'terlambat';
                            $lateMinutes = $minutesSinceStart;
                        }
                    }
                } elseif ($action === 'kosong') {
                    // Hanya boleh jika menit >= durasi * absent_threshold_pct / 100 (sekretaris). Admin boleh kapan saja.
                    $thresholdMinutes = ceil($durationMinutes * ($absentThresholdPct / 100));
                    if (!$user->isAdmin() && $minutesSinceStart < $thresholdMinutes) {
                        $allowedTime = $startTime->copy()->addMinutes($thresholdMinutes)->format('H:i');
                        throw ValidationException::withMessages([
                            'message' => ["Guru belum dapat dinyatakan tidak hadir (kosong) sebelum jam {$allowedTime}."],
                        ]);
                    }
                    $status = 'kosong';
                    $arrivedAt = null;
                    $lateMinutes = 0;
                } else {
                    // izin | sakit | dinas
                    $status = $action;
                    $arrivedAt = null;
                    $lateMinutes = 0;
                }

                if ($isCorrection) {
                    $oldData = $attendance->toArray();
                    $attendance->update([
                        'status' => $status,
                        'arrived_at' => $arrivedAt,
                        'late_minutes' => $lateMinutes,
                        'note' => $note ?? $attendance->note,
                        'updated_by' => $user->id,
                    ]);

                    AuditLog::log(
                        actor: $user,
                        action: 'update',
                        tableName: 'attendances',
                        recordId: $attendance->id,
                        detail: [
                            'before' => $oldData,
                            'after' => $attendance->fresh()->toArray(),
                        ]
                    );

                    return $attendance->fresh();
                }

                // Insert baru
                $newAttendance = Attendance::create([
                    'schedule_id' => $schedule->id,
                    'date' => $todayDate,
                    'status' => $status,
                    'arrived_at' => $arrivedAt,
                    'late_minutes' => $lateMinutes,
                    'note' => $note,
                    'recorded_by' => $user->id,
                    'updated_by' => null,
                ]);

                AuditLog::log(
                    actor: $user,
                    action: 'insert',
                    tableName: 'attendances',
                    recordId: $newAttendance->id,
                    detail: $newAttendance->toArray()
                );

                return $newAttendance;
            });
        } catch (QueryException $e) {
            // Tangkap bentrok unique(schedule_id, date) saat dua sekretaris menekan bersamaan
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'uq_attendance_schedule_date')) {
                throw ValidationException::withMessages([
                    'message' => ['Slot ini sudah diabsen oleh sekretaris lain.'],
                ]);
            }
            throw $e;
        }
    }
}

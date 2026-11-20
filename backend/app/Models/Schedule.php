<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'day_of_week',
        'period_id',
        'subject_id',
        'teacher_id',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'schedule_id');
    }

    public static function dayName(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => 'Tidak diketahui',
        };
    }

    protected static function booted(): void
    {
        static::saving(function (Schedule $schedule) {
            // Anti-bentrok kelas
            $classConflict = static::query()
                ->when($schedule->id, fn ($q) => $q->where('id', '!=', $schedule->id))
                ->where('class_id', $schedule->class_id)
                ->where('day_of_week', $schedule->day_of_week)
                ->where('period_id', $schedule->period_id)
                ->with(['subject', 'schoolClass'])
                ->first();

            if ($classConflict) {
                $className = $classConflict->schoolClass?->name ?? 'Terpilih';
                $subjectName = $classConflict->subject?->name ?? 'lain';
                throw new InvalidArgumentException("Kelas {$className} sudah punya mapel {$subjectName} pada slot yang sama.");
            }

            // Anti-bentrok guru
            $teacherConflict = static::query()
                ->when($schedule->id, fn ($q) => $q->where('id', '!=', $schedule->id))
                ->where('teacher_id', $schedule->teacher_id)
                ->where('day_of_week', $schedule->day_of_week)
                ->where('period_id', $schedule->period_id)
                ->with(['teacher', 'schoolClass'])
                ->first();

            if ($teacherConflict) {
                $teacherName = $teacherConflict->teacher?->name ?? 'Terpilih';
                $className = $teacherConflict->schoolClass?->name ?? 'lain';
                throw new InvalidArgumentException("Guru {$teacherName} sudah mengajar di kelas {$className} pada slot yang sama.");
            }
        });
    }
}


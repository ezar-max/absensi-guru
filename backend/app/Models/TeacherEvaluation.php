<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherEvaluation extends Model
{
    use HasFactory;

    public const RATINGS = [
        [
            'value' => 'sangat_baik',
            'label' => 'Sangat baik',
            'criteria' => 'Guru hadir tepat waktu',
        ],
        [
            'value' => 'baik',
            'label' => 'Baik',
            'criteria' => 'Terlambat sampai 5 menit',
        ],
        [
            'value' => 'cukup',
            'label' => 'Cukup',
            'criteria' => 'Terlambat lebih dari 5 menit',
        ],
        [
            'value' => 'kurang',
            'label' => 'Kurang',
            'criteria' => 'Kelas kosong atau guru tidak hadir',
        ],
    ];

    protected $fillable = [
        'class_id',
        'period_id',
        'subject_id',
        'teacher_id',
        'date',
        'rating',
        'teacher_present',
        'note',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'teacher_present' => 'boolean',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

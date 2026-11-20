<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'start_time',
        'end_time',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'period_id');
    }

    public static function hasOverlap(string $start, string $end, ?int $ignoreId = null): bool
    {
        return static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start, $end) {
                // Two intervals [s1, e1] and [s2, e2] overlap if s1 < e2 and e1 > s2
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->exists();
    }

    protected static function booted(): void
    {
        static::saving(function (Period $period) {
            if ($period->end_time <= $period->start_time) {
                throw new InvalidArgumentException('Jam selesai harus lebih besar dari jam mulai.');
            }

            if (static::hasOverlap($period->start_time, $period->end_time, $period->id)) {
                throw new InvalidArgumentException('Jam pelajaran tidak boleh tumpang tindih dengan jam pelajaran lain.');
            }
        });
    }
}


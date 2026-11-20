<?php

namespace App\Exports;

use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherRecapExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected int $month;
    protected int $year;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection(): Collection
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($this->year, $this->month, 1)->endOfMonth()->toDateString();

        $teachers = Teacher::with(['schedules.attendances' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        }])->orderBy('name')->get();

        return $teachers->map(function (Teacher $teacher) {
            $attendances = $teacher->schedules->flatMap->attendances;

            $total = $attendances->count();
            $hadir = $attendances->where('status', 'hadir')->count();
            $terlambat = $attendances->where('status', 'terlambat')->count();
            $kosong = $attendances->where('status', 'kosong')->count();
            $izin = $attendances->where('status', 'izin')->count();
            $sakit = $attendances->where('status', 'sakit')->count();
            $dinas = $attendances->where('status', 'dinas')->count();
            $totalLateMin = $attendances->sum('late_minutes');

            $pct = $total > 0 ? round((($hadir + $terlambat) / $total) * 100, 1) : 0;

            return [
                'name' => $teacher->name,
                'nip' => $teacher->nip ?? '-',
                'total_slot' => $total,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'kosong' => $kosong,
                'izin' => $izin,
                'sakit' => $sakit,
                'dinas' => $dinas,
                'total_late_minutes' => $totalLateMin,
                'percentage' => $pct . '%',
            ];
        });
    }

    public function headings(): array
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y');
        return [
            'Nama Guru',
            'NIP',
            'Total Slot Diabsen',
            'Hadir Tepat Waktu',
            'Terlambat',
            'Kosong',
            'Izin',
            'Sakit',
            'Dinas',
            'Total Menit Terlambat',
            'Persentase Hadir (%)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}


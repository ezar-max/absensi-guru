<?php

namespace App\Exports;

use App\Models\TeacherEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherRatingRecapExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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

        $ratings = collect(TeacherEvaluation::RATINGS)->mapWithKeys(
            fn (array $rating) => [$rating['value'] => $rating['label']]
        );

        return TeacherEvaluation::with([
            'schoolClass:id,name,major',
            'period:id,label,start_time,end_time',
            'subject:id,name',
            'teacher:id,name,nip',
            'recordedBy:id,name',
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn (TeacherEvaluation $evaluation) => [
                'date' => $evaluation->date->toDateString(),
                'class' => $evaluation->schoolClass?->name ?? '-',
                'major' => $evaluation->schoolClass?->major ?? '-',
                'period' => $evaluation->period?->label ?? '-',
                'subject' => $evaluation->subject?->name ?? '-',
                'teacher' => $evaluation->teacher?->name ?? '-',
                'nip' => $evaluation->teacher?->nip ?? '-',
                'teacher_presence' => $evaluation->teacher_present === null
                    ? 'Belum dicatat'
                    : ($evaluation->teacher_present ? 'Guru masuk' : 'Guru tidak masuk'),
                'rating' => $ratings[$evaluation->rating] ?? $evaluation->rating,
                'note' => $evaluation->note ?? '-',
                'recorded_by' => $evaluation->recordedBy?->name ?? '-',
            ]);
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kelas',
            'Jurusan',
            'Jam Pelajaran',
            'Mata Pelajaran',
            'Nama Guru',
            'NIP',
            'Kehadiran Guru',
            'Penilaian',
            'Catatan',
            'Dicatat Oleh',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

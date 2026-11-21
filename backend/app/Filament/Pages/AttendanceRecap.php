<?php

namespace App\Filament\Pages;

use App\Exports\TeacherRatingRecapExport;
use App\Models\TeacherEvaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceRecap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Absensi';
    protected static ?string $navigationLabel = 'Rekap Kehadiran';
    protected static ?string $title = 'Rekapitulasi Kehadiran Bulanan';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.attendance-recap';

    public int $month;
    public int $year;
    public string $search = '';
    public ?int $selectedEvaluationId = null;

    public function mount(): void
    {
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
    }

    public function getMonthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $fileName = "rekap-kehadiran-guru-{$this->year}-{$this->month}.xlsx";

        return Excel::download(new TeacherRatingRecapExport($this->month, $this->year), $fileName);
    }

    public function exportPdf()
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y');
        $items = (new TeacherRatingRecapExport($this->month, $this->year))->collection()->toArray();
        $pdf = Pdf::loadView('exports.recap-pdf', [
            'monthName' => $monthName,
            'items' => $items,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "rekap-kehadiran-guru-{$this->year}-{$this->month}.pdf"
        );
    }

    public function showDetails(int $evaluationId): void
    {
        abort_unless(
            TeacherEvaluation::query()
                ->whereKey($evaluationId)
                ->whereBetween('date', $this->dateRange())
                ->exists(),
            404
        );

        $this->selectedEvaluationId = $evaluationId;
    }

    public function closeDetails(): void
    {
        $this->selectedEvaluationId = null;
    }

    public function getViewData(): array
    {
        [$startDate, $endDate] = $this->dateRange();
        $evaluations = TeacherEvaluation::query()
            ->with([
                'schoolClass:id,name,major',
                'period:id,label,start_time,end_time',
                'subject:id,name',
                'teacher:id,name,nip',
                'recordedBy:id,name',
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $ratingLabels = collect(TeacherEvaluation::RATINGS)->pluck('label', 'value');
        $teacherSummaries = $evaluations
            ->groupBy('teacher_id')
            ->map(function ($teacherEvaluations) use ($ratingLabels) {
                $teacher = $teacherEvaluations->first()->teacher;
                $ratingCounts = $ratingLabels
                    ->keys()
                    ->mapWithKeys(fn (string $rating) => [
                        $rating => $teacherEvaluations->where('rating', $rating)->count(),
                    ]);

                return [
                    'teacher' => $teacher?->name ?? '-',
                    'total' => $teacherEvaluations->count(),
                    'rating_counts' => $ratingCounts,
                    'kurang_count' => $ratingCounts['kurang'] ?? 0,
                ];
            })
            ->sortBy('teacher')
            ->sortByDesc('kurang_count')
            ->values();
        $attentionTeachers = $teacherSummaries
            ->filter(fn (array $summary) => $summary['kurang_count'] > 0)
            ->values();

        $rows = $evaluations->map(fn (TeacherEvaluation $evaluation) => [
            'id' => $evaluation->id,
            'date' => $evaluation->date->toDateString(),
            'class' => $evaluation->schoolClass?->name ?? '-',
            'major' => $evaluation->schoolClass?->major ?? '-',
            'period' => $evaluation->period?->label ?? '-',
            'subject' => $evaluation->subject?->name ?? '-',
            'teacher' => $evaluation->teacher?->name ?? '-',
            'teacher_presence' => $evaluation->teacher_present === null
                ? 'Belum dicatat'
                : ($evaluation->teacher_present ? 'Guru masuk' : 'Guru tidak masuk'),
            'teacher_present' => $evaluation->teacher_present,
            'rating' => $ratingLabels[$evaluation->rating] ?? $evaluation->rating,
            'note' => $evaluation->note,
            'recorded_by' => $evaluation->recordedBy?->name ?? '-',
        ]);

        if (trim($this->search) !== '') {
            $query = mb_strtolower(trim($this->search));
            $rows = $rows->filter(fn (array $row) =>
                str_contains(mb_strtolower($row['teacher']), $query)
                || str_contains(mb_strtolower($row['class']), $query)
                || str_contains(mb_strtolower($row['subject']), $query)
                || str_contains(mb_strtolower($row['recorded_by']), $query)
                || str_contains(mb_strtolower($row['note'] ?? ''), $query)
            )->values();
        }

        $selectedEvaluation = $this->selectedEvaluationId
            ? TeacherEvaluation::query()
                ->with([
                    'schoolClass:id,name,major',
                    'period:id,label,start_time,end_time',
                    'subject:id,name',
                    'teacher:id,name,nip',
                    'recordedBy:id,name',
                ])
                ->whereKey($this->selectedEvaluationId)
                ->whereBetween('date', [$startDate, $endDate])
                ->first()
            : null;

        $carbonStart = Carbon::parse($startDate);
        $carbonEnd = Carbon::parse($endDate);

        return [
            'rows' => $rows,
            'teacherSummaries' => $teacherSummaries,
            'attentionTeachers' => $attentionTeachers,
            'selectedEvaluation' => $selectedEvaluation,
            'totalSubmissions' => $evaluations->count(),
            'totalLowRatings' => $teacherSummaries->sum('kurang_count'),
            'teacherPresentCount' => $evaluations->filter(fn (TeacherEvaluation $evaluation) => $evaluation->teacher_present === true)->count(),
            'teacherAbsentCount' => $evaluations->filter(fn (TeacherEvaluation $evaluation) => $evaluation->teacher_present === false)->count(),
            'periodLabel' => $carbonStart->translatedFormat('d F Y') . ' – ' . $carbonEnd->translatedFormat('d F Y'),
            'monthName' => $carbonStart->translatedFormat('F Y'),
        ];
    }

    private function dateRange(): array
    {
        $start = Carbon::create($this->year, $this->month, 1)->startOfMonth();

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }
}

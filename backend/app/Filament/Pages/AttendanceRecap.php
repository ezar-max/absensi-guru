<?php

namespace App\Filament\Pages;

use App\Exports\TeacherRecapExport;
use App\Models\SchoolClass;
use App\Models\Teacher;
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
    public string $viewMode = 'teacher'; // 'teacher' | 'class'
    public string $search = '';

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
        $fileName = "rekap-absensi-guru-{$this->year}-{$this->month}.xlsx";
        return Excel::download(new TeacherRecapExport($this->month, $this->year), $fileName);
    }

    public function exportPdf()
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y');
        $items = (new TeacherRecapExport($this->month, $this->year))->collection()->toArray();

        $pdf = Pdf::loadView('exports.recap-pdf', [
            'monthName' => $monthName,
            'items' => $items,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "rekap-absensi-guru-{$this->year}-{$this->month}.pdf"
        );
    }

    public function getViewData(): array
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($this->year, $this->month, 1)->endOfMonth()->toDateString();
        $carbonStart = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $carbonEnd = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        // Data Guru
        $teachersQuery = Teacher::with(['schedules.attendances' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        }])->orderBy('name');

        $teachers = $teachersQuery->get();

        $teacherRows = $teachers->map(function (Teacher $teacher) {
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

            // Generate avatar initials
            $words = explode(' ', trim($teacher->name));
            $initials = '';
            foreach (array_slice($words, 0, 2) as $w) {
                $initials .= strtoupper(substr($w, 0, 1));
            }
            if (empty($initials)) {
                $initials = 'G';
            }

            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'initials' => $initials,
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
                'numeric_pct' => $pct,
            ];
        });

        // Filter search jika ada
        if (!empty(trim($this->search))) {
            $q = strtolower(trim($this->search));
            $filteredTeacherRows = $teacherRows->filter(function ($r) use ($q) {
                return str_contains(strtolower($r['name']), $q) || str_contains(strtolower($r['nip']), $q);
            })->values();
        } else {
            $filteredTeacherRows = $teacherRows;
        }

        // Data Kelas
        $classes = SchoolClass::with(['schedules.attendances' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        }])->orderBy('name')->get();

        $classRows = $classes->map(function (SchoolClass $class) {
            $attendances = $class->schedules->flatMap->attendances;
            $total = $attendances->count();
            $hadir = $attendances->where('status', 'hadir')->count();
            $terlambat = $attendances->where('status', 'terlambat')->count();
            $kosong = $attendances->where('status', 'kosong')->count();
            $izin = $attendances->where('status', 'izin')->count();
            $sakit = $attendances->where('status', 'sakit')->count();
            $dinas = $attendances->where('status', 'dinas')->count();
            $pct = $total > 0 ? round((($hadir + $terlambat) / $total) * 100, 1) : 0;

            return [
                'id' => $class->id,
                'name' => $class->name,
                'academic_year' => $class->academic_year,
                'total_slot' => $total,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'kosong' => $kosong,
                'izin' => $izin,
                'sakit' => $sakit,
                'dinas' => $dinas,
                'percentage' => $pct . '%',
                'numeric_pct' => $pct,
            ];
        });

        // Filter search kelas jika ada
        if (!empty(trim($this->search))) {
            $q = strtolower(trim($this->search));
            $filteredClassRows = $classRows->filter(function ($r) use ($q) {
                return str_contains(strtolower($r['name']), $q) || str_contains(strtolower($r['academic_year'] ?? ''), $q);
            })->values();
        } else {
            $filteredClassRows = $classRows;
        }

        // Summary Metrics
        $totalSlotsAll = $teacherRows->sum('total_slot');
        $totalHadirAll = $teacherRows->sum('hadir');
        $totalTerlambatAll = $teacherRows->sum('terlambat');
        $totalKosongAll = $teacherRows->sum('kosong');
        $totalIzinAll = $teacherRows->sum(fn ($r) => $r['izin'] + $r['sakit'] + $r['dinas']);
        $totalLateMinutesAll = $teacherRows->sum('total_late_minutes');
        $overallRate = $totalSlotsAll > 0 ? round((($totalHadirAll + $totalTerlambatAll) / $totalSlotsAll) * 100, 1) : 0;

        return [
            'teacherRows' => $filteredTeacherRows,
            'classRows' => $filteredClassRows,
            'rawTeacherCount' => $teacherRows->count(),
            'rawClassCount' => $classRows->count(),
            'totalSlotsAll' => $totalSlotsAll,
            'totalHadirAll' => $totalHadirAll,
            'totalTerlambatAll' => $totalTerlambatAll,
            'totalKosongAll' => $totalKosongAll,
            'totalIzinAll' => $totalIzinAll,
            'totalLateMinutesAll' => $totalLateMinutesAll,
            'overallRate' => $overallRate,
            'periodLabel' => $carbonStart->translatedFormat('d F Y') . ' – ' . $carbonEnd->translatedFormat('d F Y'),
            'monthName' => $carbonStart->translatedFormat('F Y'),
        ];
    }
}

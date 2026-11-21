<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $now = Carbon::now();
        $todayDate = $now->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        // Total slot jadwal hari ini
        $totalSlots = Schedule::where('day_of_week', $dayOfWeek)->count();

        // Absensi hari ini
        $attendances = Attendance::whereDate('date', $todayDate)->get();

        $hadir = $attendances->where('status', 'hadir')->count();
        $terlambat = $attendances->where('status', 'terlambat')->count();
        $kosong = $attendances->where('status', 'kosong')->count();
        $izinSakitDinas = $attendances->whereIn('status', ['izin', 'sakit', 'dinas'])->count();

        $sudahDiabsen = $attendances->count();
        $belumDiabsen = max(0, $totalSlots - $sudahDiabsen);

        $pctHadir = $totalSlots > 0 ? round(($hadir / $totalSlots) * 100, 1) : 0;

        return [
            Stat::make('Total Slot Hari Ini', $totalSlots)
                ->description('Slot jam pelajaran aktif terjadwal')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Hadir Tepat Waktu', $hadir)
                ->description($totalSlots > 0 ? "{$pctHadir}% dari total jadwal hari ini" : 'Belum ada jadwal aktif')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Terlambat', $terlambat)
                ->description($terlambat > 0 ? "{$terlambat} guru melewati toleransi waktu" : 'Nol keterlambatan hari ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Kosong (Tidak Hadir)', $kosong)
                ->description($kosong > 0 ? 'Perlu tindakan / guru pengganti' : 'Nihil / Semua guru hadir')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($kosong > 0 ? 'danger' : 'gray'),

            Stat::make('Izin / Sakit / Dinas', $izinSakitDinas)
                ->description($izinSakitDinas > 0 ? "{$izinSakitDinas} guru berhalangan resmi" : 'Tidak ada izin/sakit/dinas')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Belum Diabsen', $belumDiabsen)
                ->description($belumDiabsen > 0 ? 'Menunggu laporan dari sekretaris' : 'Seluruh slot telah diabsen')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($belumDiabsen > 0 ? 'warning' : 'success'),
        ];
    }
}

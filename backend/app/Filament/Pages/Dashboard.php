<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttentionNeededTable;
use App\Filament\Widgets\TodayStatsOverview;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Beranda';
    protected static ?string $title = 'Dashboard Presensi Guru';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;

    public function getSubheading(): ?string
    {
        return 'Pemantauan kehadiran pengajar secara real-time dan rekapitulasi aktivitas belajar mengajar hari ini.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gridJadwal')
                ->label('Grid Jadwal')
                ->icon('heroicon-m-table-cells')
                ->color('gray')
                ->url(route('filament.admin.pages.manage-schedule-grid')),

            Action::make('rekapAbsensi')
                ->label('Rekap Absensi')
                ->icon('heroicon-m-chart-bar')
                ->color('gray')
                ->url(route('filament.admin.pages.attendance-recap')),

            Action::make('pengaturan')
                ->label('Aturan Sistem')
                ->icon('heroicon-m-adjustments-horizontal')
                ->color('primary')
                ->url(route('filament.admin.pages.manage-settings')),
        ];
    }

    public function getWidgets(): array
    {
        return [
            TodayStatsOverview::class,
            AttentionNeededTable::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}

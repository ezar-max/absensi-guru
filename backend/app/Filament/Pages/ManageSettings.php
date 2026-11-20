<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?string $title = 'Pengaturan Kebijakan Absensi';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.manage-settings';

    public int $late_tolerance_min = 10;
    public int $absent_threshold_pct = 50;
    public int $edit_lock_min = 15;

    public function mount(): void
    {
        $this->late_tolerance_min = Setting::getInt('late_tolerance_min', 10);
        $this->absent_threshold_pct = Setting::getInt('absent_threshold_pct', 50);
        $this->edit_lock_min = Setting::getInt('edit_lock_min', 15);
    }

    public function setLateTolerance(int $min): void
    {
        $this->late_tolerance_min = max(0, min($min, 60));
    }

    public function setAbsentThreshold(int $pct): void
    {
        $this->absent_threshold_pct = max(10, min($pct, 90));
    }

    public function setEditLock(int $min): void
    {
        $this->edit_lock_min = max(1, min($min, 120));
    }

    public function resetDefaults(): void
    {
        $this->late_tolerance_min = 10;
        $this->absent_threshold_pct = 50;
        $this->edit_lock_min = 15;

        Notification::make()
            ->title('Formulir direset ke nilai default')
            ->info()
            ->send();
    }

    public function save(): void
    {
        $this->validate([
            'late_tolerance_min' => 'required|integer|min:0|max:60',
            'absent_threshold_pct' => 'required|integer|min:10|max:90',
            'edit_lock_min' => 'required|integer|min:1|max:120',
        ], [
            'late_tolerance_min.required' => 'Toleransi keterlambatan wajib diisi.',
            'late_tolerance_min.min' => 'Toleransi keterlambatan minimal 0 menit.',
            'late_tolerance_min.max' => 'Toleransi keterlambatan maksimal 60 menit.',
            'absent_threshold_pct.required' => 'Ambang batas kosong wajib diisi.',
            'absent_threshold_pct.min' => 'Ambang batas kosong minimal 10%.',
            'absent_threshold_pct.max' => 'Ambang batas kosong maksimal 90%.',
            'edit_lock_min.required' => 'Batas waktu koreksi wajib diisi.',
            'edit_lock_min.min' => 'Batas waktu koreksi minimal 1 menit.',
            'edit_lock_min.max' => 'Batas waktu koreksi maksimal 120 menit.',
        ]);

        Setting::set('late_tolerance_min', $this->late_tolerance_min);
        Setting::set('absent_threshold_pct', $this->absent_threshold_pct);
        Setting::set('edit_lock_min', $this->edit_lock_min);

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan')
            ->body('Parameter absensi dan batas waktu sistem telah diperbarui secara global.')
            ->success()
            ->send();
    }
}

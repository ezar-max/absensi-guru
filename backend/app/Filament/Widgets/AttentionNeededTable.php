<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttentionNeededTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Perlu Perhatian Hari Ini (Slot Terlewat & Guru Kosong)';

    public function table(Table $table): Table
    {
        $now = Carbon::now();
        $todayDate = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeekIso;

        // Helper untuk mencocokkan tanggal presensi secara akurat
        $getTodayAttendance = function (Schedule $record) use ($todayDate): ?Attendance {
            $att = $record->attendances->first(function ($a) use ($todayDate) {
                $d = $a->date instanceof Carbon ? $a->date->toDateString() : substr((string) $a->date, 0, 10);
                return $d === $todayDate;
            });

            if (! $att && $record->relationLoaded('attendances')) {
                $att = Attendance::where('schedule_id', $record->id)->whereDate('date', $todayDate)->first();
            }

            return $att;
        };

        return $table
            ->description('Daftar jam pelajaran yang telah lewat jam selesai namun belum diabsen, atau guru berhalangan hadir.')
            ->query(
                Schedule::query()
                    ->where('schedules.day_of_week', $dayOfWeek)
                    ->join('periods', 'schedules.period_id', '=', 'periods.id')
                    ->where(function (Builder $q) use ($todayDate, $currentTime) {
                        // 1. Sudah lewat jam selesai dan belum diabsen
                        $q->where('periods.end_time', '<=', $currentTime)
                          ->whereDoesntHave('attendances', fn ($att) => $att->whereDate('date', $todayDate));

                        // 2. Atau sudah diabsen tapi statusnya kosong, izin, sakit, atau dinas
                        $q->orWhereHas('attendances', fn ($att) => $att->whereDate('date', $todayDate)->whereIn('status', ['kosong', 'izin', 'sakit', 'dinas']));
                    })
                    ->with(['schoolClass', 'period', 'subject', 'teacher', 'attendances' => fn ($att) => $att->whereDate('date', $todayDate)])
                    ->select('schedules.*')
            )
            ->columns([
                Tables\Columns\TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->fontFamily('font-mono')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('period.label')
                    ->label('Jam Ke')
                    ->description(fn (Schedule $record) => substr($record->period->start_time, 0, 5) . ' - ' . substr($record->period->end_time, 0, 5)),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Pengampu'),
                Tables\Columns\TextColumn::make('issue_status')
                    ->label('Kondisi')
                    ->badge()
                    ->state(function (Schedule $record) use ($getTodayAttendance): string {
                        $att = $getTodayAttendance($record);
                        if (! $att) {
                            return 'Terlewat Belum Diabsen';
                        }

                        return match ($att->status) {
                            'kosong' => 'Guru Kosong (Tidak Hadir)',
                            'izin' => 'Izin: ' . ($att->note ?: 'Terkonfirmasi'),
                            'sakit' => 'Sakit: ' . ($att->note ?: 'Terkonfirmasi'),
                            'dinas' => 'Dinas Luar: ' . ($att->note ?: 'Terkonfirmasi'),
                            'hadir' => 'Hadir (Diabsen Admin)',
                            'terlambat' => 'Terlambat (' . $att->late_minutes . ' mnt)',
                            default => ucfirst($att->status),
                        };
                    })
                    ->color(function (string $state): string {
                        if (str_contains($state, 'Kosong')) return 'danger';
                        if (str_contains($state, 'Terlewat')) return 'warning';
                        if (str_contains($state, 'Hadir')) return 'success';
                        if (str_contains($state, 'Izin') || str_contains($state, 'Dinas')) return 'info';
                        if (str_contains($state, 'Sakit')) return 'warning';
                        return 'gray';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('markHadir')
                    ->label('Catat Hadir')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Catat Hadir')
                    ->modalDescription(fn (Schedule $record) => "Tandai guru {$record->teacher?->name} ({$record->subject?->name}) sebagai Hadir pada jam ini?")
                    ->action(function (Schedule $record) use ($todayDate): void {
                        $att = Attendance::updateOrCreate(
                            ['schedule_id' => $record->id, 'date' => $todayDate],
                            [
                                'status' => 'hadir',
                                'arrived_at' => $record->period?->start_time ?? now()->format('H:i:s'),
                                'late_minutes' => 0,
                                'note' => 'Dicatat hadir langsung oleh Admin dari dashboard',
                                'recorded_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]
                        );

                        AuditLog::log(Auth::user(), 'update', 'attendances', $att->id, ['action' => 'mark_hadir_from_dashboard']);

                        Notification::make()->title('Slot berhasil dicatat sebagai Hadir')->success()->send();
                    }),

                Tables\Actions\Action::make('markKosong')
                    ->label('Catat Kosong')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->button()
                    ->size('xs')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Catat Kosong')
                    ->modalDescription(fn (Schedule $record) => "Tandai guru {$record->teacher?->name} ({$record->subject?->name}) sebagai TIDAK HADIR / KOSONG pada jam ini?")
                    ->visible(fn (Schedule $record) => $getTodayAttendance($record)?->status !== 'kosong')
                    ->action(function (Schedule $record) use ($todayDate): void {
                        $att = Attendance::updateOrCreate(
                            ['schedule_id' => $record->id, 'date' => $todayDate],
                            [
                                'status' => 'kosong',
                                'arrived_at' => null,
                                'late_minutes' => 0,
                                'note' => 'Dicatat kosong langsung oleh Admin dari dashboard',
                                'recorded_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]
                        );

                        AuditLog::log(Auth::user(), 'update', 'attendances', $att->id, ['action' => 'mark_kosong_from_dashboard']);

                        Notification::make()->title('Slot berhasil dicatat sebagai Kosong')->success()->send();
                    }),

                Tables\Actions\Action::make('markIzin')
                    ->label('Catat Izin/Sakit')
                    ->icon('heroicon-m-information-circle')
                    ->color('info')
                    ->button()
                    ->size('xs')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Keterangan')
                            ->options([
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'dinas' => 'Dinas Luar',
                            ])
                            ->default('izin')
                            ->required(),
                        Forms\Components\TextInput::make('note')
                            ->label('Alasan / Catatan')
                            ->placeholder('Contoh: Sakit flu, Izin dinas MGMP, dll')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Schedule $record, array $data) use ($todayDate): void {
                        $att = Attendance::updateOrCreate(
                            ['schedule_id' => $record->id, 'date' => $todayDate],
                            [
                                'status' => $data['status'],
                                'arrived_at' => null,
                                'late_minutes' => 0,
                                'note' => $data['note'],
                                'recorded_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                            ]
                        );

                        AuditLog::log(Auth::user(), 'update', 'attendances', $att->id, ['action' => 'mark_izin_from_dashboard', 'data' => $data]);

                        Notification::make()->title("Slot berhasil dicatat sebagai {$data['status']}")->success()->send();
                    }),

                Tables\Actions\Action::make('resetSlot')
                    ->label('Batal / Reset')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->button()
                    ->size('xs')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Catatan Presensi?')
                    ->modalDescription('Presensi untuk slot ini akan dihapus dan status akan kembali menjadi Terlewat Belum Diabsen.')
                    ->visible(fn (Schedule $record) => $getTodayAttendance($record) !== null)
                    ->action(function (Schedule $record) use ($todayDate, $getTodayAttendance): void {
                        $att = $getTodayAttendance($record);
                        if ($att) {
                            AuditLog::log(Auth::user(), 'delete', 'attendances', $att->id, ['action' => 'reset_slot_from_dashboard']);
                            $att->delete();
                            Notification::make()->title('Catatan presensi berhasil direset')->success()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Semua Jam Pelajaran Terkendali')
            ->emptyStateDescription('Tidak ada slot pelajaran yang terlewat atau guru tercatat kosong untuk hari ini.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}

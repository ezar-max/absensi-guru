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

        return $table
            ->description('Daftar jam pelajaran yang telah lewat jam selesai namun belum diabsen, atau guru tidak hadir.')
            ->query(
                Schedule::query()
                    ->where('schedules.day_of_week', $dayOfWeek)
                    ->join('periods', 'schedules.period_id', '=', 'periods.id')
                    ->where(function (Builder $q) use ($todayDate, $currentTime) {
                        // 1. Sudah lewat jam selesai dan belum diabsen
                        $q->where('periods.end_time', '<=', $currentTime)
                          ->whereDoesntHave('attendances', fn ($att) => $att->where('date', $todayDate));

                        // 2. Atau sudah diabsen tapi statusnya kosong
                        $q->orWhereHas('attendances', fn ($att) => $att->where('date', $todayDate)->where('status', 'kosong'));
                    })
                    ->with(['schoolClass', 'period', 'subject', 'teacher', 'attendances' => fn ($att) => $att->where('date', $todayDate)])
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
                    ->state(function (Schedule $record) use ($todayDate): string {
                        $att = $record->attendances->firstWhere('date', $todayDate);
                        if ($att && $att->status === 'kosong') {
                            return 'Guru Kosong (Tidak Hadir)';
                        }
                        return 'Terlewat Belum Diabsen';
                    })
                    ->color(fn (string $state): string => str_contains($state, 'Kosong') ? 'danger' : 'warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('markKosong')
                    ->label('Catat Kosong')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->button()
                    ->size('xs')
                    ->requiresConfirmation()
                    ->visible(fn (Schedule $record) => $record->attendances->firstWhere('date', $todayDate)?->status !== 'kosong')
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
            ])
            ->emptyStateHeading('Semua Jam Pelajaran Terkendali')
            ->emptyStateDescription('Tidak ada slot pelajaran yang terlewat atau guru tercatat kosong untuk hari ini.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}

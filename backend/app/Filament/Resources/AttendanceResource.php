<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Absensi';
    protected static ?string $navigationLabel = 'Data Absensi';
    protected static ?string $modelLabel = 'Absensi Guru';
    protected static ?string $pluralModelLabel = 'Data Absensi Guru';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('schedule_id')
                    ->label('Jadwal Pelajaran')
                    ->relationship('schedule', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->schoolClass?->name} - {$record->subject?->name} ({$record->teacher?->name})")
                    ->disabledOn('edit')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->disabledOn('edit')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'kosong' => 'Kosong (Tidak Hadir)',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'dinas' => 'Dinas Luar',
                    ])
                    ->required(),
                Forms\Components\TimePicker::make('arrived_at')
                    ->label('Jam Datang')
                    ->seconds(false),
                Forms\Components\TextInput::make('late_minutes')
                    ->label('Menit Keterlambatan')
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('note')
                    ->label('Catatan / Alasan Koreksi')
                    ->helperText('Wajib diisi bila melakukan koreksi data kehadiran')
                    ->required(fn (string $context): bool => $context === 'edit')
                    ->maxLength(500),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule.schoolClass.name')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('schedule.period.label')
                    ->label('Jam Ke')
                    ->description(fn (Attendance $record) => $record->schedule?->period ? substr($record->schedule->period->start_time, 0, 5) . ' - ' . substr($record->schedule->period->end_time, 0, 5) : '')
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule.subject.name')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('schedule.teacher.name')
                    ->label('Guru')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'kosong' => 'danger',
                        'izin', 'sakit', 'dinas' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('arrived_at')
                    ->label('Jam Datang')
                    ->time('H:i')
                    ->description(fn (Attendance $record) => $record->late_minutes > 0 ? "+{$record->late_minutes} mnt" : null)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(25)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Dikoreksi Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'kosong' => 'Kosong',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'dinas' => 'Dinas',
                    ]),
                Tables\Filters\SelectFilter::make('class')
                    ->label('Kelas')
                    ->relationship('schedule.schoolClass', 'name'),
                Tables\Filters\SelectFilter::make('teacher')
                    ->label('Guru')
                    ->relationship('schedule.teacher', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->mutateRecordDataUsing(function (array $data): array {
                        return $data;
                    })
                    ->using(function (Attendance $record, array $data): Attendance {
                        $oldData = $record->toArray();
                        $user = Auth::user();

                        $record->update([
                            'status' => $data['status'],
                            'arrived_at' => $data['arrived_at'] ?? null,
                            'late_minutes' => $data['late_minutes'] ?? 0,
                            'note' => $data['note'],
                            'updated_by' => $user->id,
                        ]);

                        AuditLog::log(
                            actor: $user,
                            action: 'update',
                            tableName: 'attendances',
                            recordId: $record->id,
                            detail: [
                                'reason' => $data['note'],
                                'before' => $oldData,
                                'after' => $record->fresh()->toArray(),
                            ]
                        );

                        return $record;
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttendances::route('/'),
        ];
    }
}


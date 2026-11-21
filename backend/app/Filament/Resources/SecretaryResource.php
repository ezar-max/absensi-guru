<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecretaryResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use App\Models\AttendanceReminder;
use App\Models\Period;
use App\Services\FcmMessagingException;
use App\Services\FcmMessagingService;
use Illuminate\Support\Facades\Auth;

class SecretaryResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Data Master';
    protected static ?string $navigationLabel = 'Akun Sekretaris';
    protected static ?string $modelLabel = 'Akun Sekretaris';
    protected static ?string $pluralModelLabel = 'Akun Sekretaris';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'secretary');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Siswa / Sekretaris')
                    ->placeholder('Contoh: Fredi Pratama')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('nis')
                    ->label('NIS (Nomor Induk Siswa)')
                    ->placeholder('Contoh: 20241001')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(30)
                    ->helperText('Digunakan oleh siswa untuk login ke aplikasi mobile'),
                Forms\Components\TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->helperText('Minimal 8 karakter'),
                Forms\Components\Select::make('classes')
                    ->label('Kelas yang Ditugaskan')
                    ->relationship('classes', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Satu sekretaris dapat ditugaskan ke satu atau lebih kelas'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->helperText('Jika dinonaktifkan, sekretaris tidak dapat login ke aplikasi mobile'),
                Forms\Components\Hidden::make('role')->default('secretary'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Sekretaris')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('classes.name')
                    ->label('Kelas')
                    ->badge()
                    ->separator(',')
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sendAttendanceReminder')
                    ->label('Kirim Pengingat Penilaian')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->modalHeading('Kirim Pengingat Pengisian Penilaian')
                    ->form([
                        Forms\Components\Select::make('period_id')
                            ->label('Jam Pelajaran')
                            ->options(Period::query()->orderBy('start_time')->pluck('label', 'id'))
                            ->required()
                            ->helperText('Pilih jam pelajaran yang perlu diingatkan.'),
                        Forms\Components\Textarea::make('message')
                            ->label('Pesan Tambahan')
                            ->default('Segera isi penilaian guru untuk jam pelajaran yang belum tercatat.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (User $record, array $data): void {
                        $period = Period::findOrFail($data['period_id']);

                        $reminder = AttendanceReminder::create([
                            'user_id' => $record->id,
                            'created_by' => Auth::id(),
                            'period_id' => $period->id,
                            'title' => 'Pengingat Penilaian ' . $period->label,
                            'message' => $data['message'],
                        ]);

                        try {
                            $sent = app(FcmMessagingService::class)->sendReminder($record, $reminder);
                        } catch (FcmMessagingException $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Pengingat tersimpan, tetapi push gagal')
                                ->body($exception->getMessage())
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($sent === 0) {
                            Notification::make()
                                ->title('Pengingat tersimpan, perangkat belum terdaftar')
                                ->body('Pengguna perlu mengaktifkan izin notifikasi dan membuka aplikasi setidaknya sekali.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Pengingat push terkirim')
                            ->body("Pengingat penilaian {$period->label} dikirim ke {$sent} perangkat milik {$record->name}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('resetPassword')
                    ->label('Reset Sandi')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('Kata Sandi Baru')
                            ->password()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['new_password']),
                        ]);

                        Notification::make()
                            ->title('Kata sandi berhasil direset')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSecretaries::route('/'),
        ];
    }
}


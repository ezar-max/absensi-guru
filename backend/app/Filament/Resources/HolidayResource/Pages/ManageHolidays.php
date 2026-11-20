<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use App\Models\Holiday;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageHolidays extends ManageRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Hari Libur (Satu Hari)'),
            Actions\Action::make('createRange')
                ->label('Tambah Rentang Libur')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->afterOrEqual('start_date'),
                    Forms\Components\TextInput::make('name')
                        ->label('Keterangan Libur')
                        ->placeholder('Misal: Libur Semester Ganjil')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $start = Carbon::parse($data['start_date']);
                    $end = Carbon::parse($data['end_date']);
                    $count = 0;

                    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                        Holiday::updateOrCreate(
                            ['date' => $date->toDateString()],
                            ['name' => $data['name']]
                        );
                        $count++;
                    }

                    Notification::make()
                        ->title("Berhasil menambahkan {$count} hari libur")
                        ->success()
                        ->send();
                }),
        ];
    }
}


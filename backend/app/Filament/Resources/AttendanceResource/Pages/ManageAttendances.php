<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\AuditLog;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageAttendances extends ManageRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Catat Absensi Manual')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['recorded_by'] = Auth::id();
                    return $data;
                })
                ->after(function ($record) {
                    AuditLog::log(
                        actor: Auth::user(),
                        action: 'insert',
                        tableName: 'attendances',
                        recordId: $record->id,
                        detail: $record->toArray()
                    );
                }),
        ];
    }
}


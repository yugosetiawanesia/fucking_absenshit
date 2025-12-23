<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAbsensi extends ViewRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->url(fn ($record) => \App\Filament\Resources\AbsensiResource::getUrl('edit', ['record' => $record->id])),
        ];
    }
}

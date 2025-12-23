<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAbsensi extends EditRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_list')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Resources\AbsensiResource::getUrl('index')),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\AbsensiResource::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data absensi berhasil diperbarui';
    }

    protected function getSavedNotificationDescription(): ?string
    {
        return 'Perubahan data absensi telah tersimpan.';
    }
}

<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Check if absensi already exists for this student and date
        $existingAbsensi = Absensi::where('siswa_id', $data['siswa_id'])
            ->whereDate('tanggal', $data['tanggal'])
            ->first();

        if ($existingAbsensi) {
            // Update existing record
            $existingAbsensi->update($data);
            return $existingAbsensi;
        }

        // Create new record
        return static::getModel()::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Resources\AbsensiResource::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data absensi berhasil disimpan';
    }

    protected function getCreatedNotificationDescription(): ?string
    {
        return 'Data absensi telah tersimpan dalam database.';
    }
}

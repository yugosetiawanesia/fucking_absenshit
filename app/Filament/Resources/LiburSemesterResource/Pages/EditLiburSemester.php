<?php

namespace App\Filament\Resources\LiburSemesterResource\Pages;

use App\Filament\Resources\LiburSemesterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLiburSemester extends EditRecord
{
    protected static string $resource = LiburSemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

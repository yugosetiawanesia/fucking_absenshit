<?php

namespace App\Filament\Resources\HariLiburResource\Pages;

use App\Filament\Resources\HariLiburResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHariLibur extends ViewRecord
{
    protected static string $resource = HariLiburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

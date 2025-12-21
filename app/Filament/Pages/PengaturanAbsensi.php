<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PengaturanAbsensi extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Absensi';

    protected static ?string $navigationLabel = 'Pengaturan Absensi';

    protected static string $view = 'filament.pages.pengaturan-absensi';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'allow_sunday_attendance' => Setting::getBool('absensi.allow_sunday_attendance', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Toggle::make('allow_sunday_attendance')
                    ->label('Izinkan absen di hari Minggu')
                    ->helperText('Jika dimatikan, hari Minggu otomatis status libur dan tidak perlu absen.')
                    ->default(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setBool('absensi.allow_sunday_attendance', (bool) ($state['allow_sunday_attendance'] ?? false));

        Notification::make()
            ->title('Pengaturan tersimpan')
            ->success()
            ->send();
    }
}

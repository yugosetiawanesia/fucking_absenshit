<?php

namespace App\Filament\Pages;

use App\Models\Semester;
use App\Models\Setting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

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
            'workdays_per_week' => (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6'),
            'semester_id' => Schema::hasTable('semesters')
                ? (Semester::query()->where('is_active', true)->value('id'))
                : null,
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
                \Filament\Forms\Components\Select::make('workdays_per_week')
                    ->label('Hari kerja')
                    ->options([
                        5 => '5 hari (Senin–Jumat)',
                        6 => '6 hari (Senin–Sabtu)',
                    ])
                    ->default(6)
                    ->required(),
                \Filament\Forms\Components\Select::make('semester_id')
                    ->label('Semester aktif')
                    ->options(function (): array {
                        if (! Schema::hasTable('semesters')) {
                            return [];
                        }

                        return Semester::query()
                            ->orderByDesc('tahun_ajaran')
                            ->orderBy('semester')
                            ->get()
                            ->mapWithKeys(function (Semester $semester): array {
                                $label = $semester->semester === 'genap' ? 'Genap' : 'Ganjil';

                                return [
                                    $semester->id => $label.' '.$semester->tahun_ajaran.' ('.$semester->tanggal_mulai?->toDateString().' - '.$semester->tanggal_selesai?->toDateString().')',
                                ];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->placeholder('Pilih semester')
                    ->helperText('Atur data semester lewat menu Absensi > Semester.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setBool('absensi.allow_sunday_attendance', (bool) ($state['allow_sunday_attendance'] ?? false));
        Setting::setString('absensi.workdays_per_week', (string) (int) ($state['workdays_per_week'] ?? 6));

        $semesterId = $state['semester_id'] ?? null;
        if ($semesterId && Schema::hasTable('semesters')) {
            Semester::query()->where('is_active', true)->update(['is_active' => false]);
            Semester::query()->whereKey($semesterId)->update(['is_active' => true]);
            Setting::setString('absensi.active_semester_id', (string) $semesterId);
        } else {
            Setting::setString('absensi.active_semester_id', null);
        }

        Notification::make()
            ->title('Pengaturan tersimpan')
            ->success()
            ->send();
    }
}

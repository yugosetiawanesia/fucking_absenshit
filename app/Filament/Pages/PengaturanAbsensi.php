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
        $legacyCooldownSeconds = (int) (Setting::getString('absensi.auto_scan_cooldown_seconds', '60') ?? '60');
        $cooldownMinutes = (int) (Setting::getString('absensi.auto_scan_cooldown_minutes', null) ?? (string) max(1, (int) ceil($legacyCooldownSeconds / 60)));

        $this->form->fill([
            'timezone' => Setting::getString('app.timezone', 'Asia/Makassar'),
            'allow_sunday_attendance' => Setting::getBool('absensi.allow_sunday_attendance', false),
            'workdays_per_week' => (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6'),
            'auto_scan_cooldown_minutes' => $cooldownMinutes,
            'semester_id' => Schema::hasTable('semesters')
                ? (Semester::query()->where('is_active', true)->value('id'))
                : null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('timezone')
                    ->label('Zona waktu')
                    ->options([
                        'Asia/Jakarta' => 'WIB (Asia/Jakarta, GMT+7)',
                        'Asia/Makassar' => 'WITA (Asia/Makassar, GMT+8)',
                        'Asia/Jayapura' => 'WIT (Asia/Jayapura, GMT+9)',
                        'UTC' => 'UTC',
                    ])
                    ->default('Asia/Makassar')
                    ->required(),
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
                \Filament\Forms\Components\TextInput::make('auto_scan_cooldown_minutes')
                    ->label('Jeda minimal scan (menit)')
                    ->numeric()
                    ->minValue(0)
                    ->default(1)
                    ->helperText('Anti double-scan: scan pulang baru bisa dilakukan setelah melewati jeda ini dari jam masuk.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setString('app.timezone', (string) ($state['timezone'] ?? 'Asia/Makassar'));
        Setting::setBool('absensi.allow_sunday_attendance', (bool) ($state['allow_sunday_attendance'] ?? false));
        Setting::setString('absensi.workdays_per_week', (string) (int) ($state['workdays_per_week'] ?? 6));
        Setting::setString('absensi.auto_scan_cooldown_minutes', (string) (int) ($state['auto_scan_cooldown_minutes'] ?? 1));

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

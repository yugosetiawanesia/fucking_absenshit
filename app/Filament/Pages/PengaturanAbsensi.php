<?php

namespace App\Filament\Pages;

use App\Models\Semester;
use App\Models\Setting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class PengaturanAbsensi extends Page
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Absensi';
    protected static ?string $navigationLabel = 'Pengaturan Absensi';
    protected static string $view = 'filament.pages.pengaturan-absensi';

    public ?array $data = [];
    public $newLogo = null;
    public $currentLogoPath = null;

    public function mount(): void
    {
        $legacyCooldownSeconds = (int) (Setting::getString('absensi.auto_scan_cooldown_seconds', '60') ?? '60');
        $cooldownMinutes = (int) (Setting::getString('absensi.auto_scan_cooldown_minutes', null) ?? (string) max(1, (int) ceil($legacyCooldownSeconds / 60)));
        
        // Load current logo dan verifikasi keberadaannya
        $logoPath = Setting::getString('school.logo_path');
        
        // Debug logging
        \Log::info('Mount - Logo Path Check', [
            'logo_path' => $logoPath,
            'exists' => $logoPath ? Storage::disk('public')->exists($logoPath) : false,
            'full_path' => $logoPath ? Storage::disk('public')->path($logoPath) : null,
            'url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'public_path' => public_path('storage'),
            'storage_path' => storage_path('app/public'),
        ]);
        
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $this->currentLogoPath = $logoPath;
        } else {
            $this->currentLogoPath = null;
            // Clear invalid path from settings
            if ($logoPath) {
                Setting::setString('school.logo_path', '');
            }
        }

        $this->form->fill([
            'timezone' => Setting::getString('app.timezone', 'Asia/Makassar'),
            'allow_sunday_attendance' => Setting::getBool('absensi.allow_sunday_attendance', false),
            'workdays_per_week' => (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6'),
            'auto_scan_cooldown_minutes' => $cooldownMinutes,
            'semester_id' => Schema::hasTable('semesters')
                ? (Semester::query()->where('is_active', true)->value('id'))
                : null,
            'school_name' => Setting::getString('school.name', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make('school_name')
                    ->label('Nama sekolah')
                    ->maxLength(255),
                
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

    public function uploadLogo()
    {
        $this->validate([
            'newLogo' => 'required|image|mimes:jpeg,png,jpg,webp|max:1024',
        ], [
            'newLogo.required' => 'Pilih file logo terlebih dahulu',
            'newLogo.image' => 'File harus berupa gambar',
            'newLogo.mimes' => 'Format file harus JPG, PNG, atau WEBP',
            'newLogo.max' => 'Ukuran file maksimal 1MB',
        ]);

        try {
            // Hapus logo lama
            if ($this->currentLogoPath && Storage::disk('public')->exists($this->currentLogoPath)) {
                Storage::disk('public')->delete($this->currentLogoPath);
            }

            // Simpan logo baru dengan nama yang unik
            $filename = 'logo-' . now()->format('YmdHis') . '.' . $this->newLogo->getClientOriginalExtension();
            $path = $this->newLogo->storeAs('school/logo', $filename, 'public');
            
            // Verifikasi file benar-benar tersimpan
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('File gagal disimpan ke storage');
            }

            // Simpan path ke setting
            Setting::setString('school.logo_path', $path);
            $this->currentLogoPath = $path;
            $this->newLogo = null; // Reset

            // Log untuk debugging
            \Log::info('Logo uploaded successfully', [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'exists' => Storage::disk('public')->exists($path)
            ]);

            Notification::make()
                ->title('Logo berhasil diupload')
                ->body('File tersimpan di: ' . $path)
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Logo upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Gagal upload logo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteLogo()
    {
        if ($this->currentLogoPath && Storage::disk('public')->exists($this->currentLogoPath)) {
            Storage::disk('public')->delete($this->currentLogoPath);
        }
        
        Setting::setString('school.logo_path', '');
        $this->currentLogoPath = null;

        Notification::make()
            ->title('Logo berhasil dihapus')
            ->success()
            ->send();
    }

    public function getLogoUrl()
    {
        if (!$this->currentLogoPath) {
            return null;
        }

        // Coba berbagai cara untuk generate URL
        $url = Storage::disk('public')->url($this->currentLogoPath);
        
        // Jika URL dimulai dengan /storage, pastikan path benar
        if (!str_starts_with($url, 'http')) {
            $url = url($url);
        }

        \Log::info('Generated Logo URL', [
            'path' => $this->currentLogoPath,
            'url' => $url,
            'exists' => Storage::disk('public')->exists($this->currentLogoPath),
        ]);

        return $url;
    }

    public function testStorageLink()
    {
        $symlinkExists = is_link(public_path('storage'));
        $targetPath = $symlinkExists ? readlink(public_path('storage')) : null;
        $targetExists = $targetPath ? file_exists($targetPath) : false;

        Notification::make()
            ->title('Storage Link Test')
            ->body(sprintf(
                'Symlink exists: %s | Target: %s | Target exists: %s',
                $symlinkExists ? 'Yes' : 'No',
                $targetPath ?? 'N/A',
                $targetExists ? 'Yes' : 'No'
            ))
            ->info()
            ->send();

        \Log::info('Storage Link Test', [
            'symlink_exists' => $symlinkExists,
            'target_path' => $targetPath,
            'target_exists' => $targetExists,
            'public_storage_path' => public_path('storage'),
            'storage_app_public' => storage_path('app/public'),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Simpan')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $state = $this->form->getState();
            
            Setting::setString('app.timezone', (string) ($state['timezone'] ?? 'Asia/Makassar'));
            Setting::setBool('absensi.allow_sunday_attendance', (bool) ($state['allow_sunday_attendance'] ?? false));
            Setting::setString('absensi.workdays_per_week', (string) (int) ($state['workdays_per_week'] ?? 6));
            Setting::setString('absensi.auto_scan_cooldown_minutes', (string) (int) ($state['auto_scan_cooldown_minutes'] ?? 1));
            Setting::setString('school.name', $state['school_name'] ?? '');
            
            // Handle semester
            $semesterId = $state['semester_id'] ?? null;
            if ($semesterId && Schema::hasTable('semesters')) {
                Semester::query()->where('is_active', true)->update(['is_active' => false]);
                Semester::query()->whereKey($semesterId)->update(['is_active' => true]);
                Setting::setString('absensi.active_semester_id', (string) $semesterId);
            } else {
                Setting::setString('absensi.active_semester_id', '');
            }
            
            Notification::make()
                ->title('Pengaturan berhasil disimpan')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan pengaturan')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
            
            throw $e;
        }
    }
}
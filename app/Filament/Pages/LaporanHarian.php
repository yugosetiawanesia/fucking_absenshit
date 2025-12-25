<?php

namespace App\Filament\Pages;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Semester;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class LaporanHarian extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Harian';
    protected static string $view = 'filament.pages.laporan-harian';

    public ?array $data = [];
    public $reportData = [];
    public $isLoading = false;

    public function mount(): void
    {
        $defaultKelas = Kelas::query()->orderBy('nama_kelas')->first();
        
        $this->form->fill([
            'tanggal' => CarbonImmutable::now()->toDateString(),
            'kelas_id' => $defaultKelas?->id,
        ]);
        
        $this->loadReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->description('Pilih tanggal dan kelas untuk melihat laporan harian')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('tanggal')
                                    ->label('Tanggal Laporan')
                                    ->required()
                                    ->default(CarbonImmutable::now())
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->loadReport()),
                                
                                Select::make('kelas_id')
                                    ->label('Kelas')
                                    ->options(fn () => Kelas::query()->orderBy('nama_kelas')->pluck('nama_kelas', 'id')->all())
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Pilih Kelas')
                                    ->default(fn () => Kelas::query()->orderBy('nama_kelas')->first()?->id)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->loadReport()),
                            ]),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function loadReport(): void
    {
        $this->isLoading = true;
        
        try {
            $state = $this->form->getState();
            $tanggal = $state['tanggal'] ?? CarbonImmutable::now()->toDateString();
            $kelasId = $state['kelas_id'] ?? null;

            if (!$kelasId) {
                $this->reportData = [];
                $this->isLoading = false;
                return;
            }

            $kelas = Kelas::query()->with('siswa')->find($kelasId);
            $siswa = $kelas?->siswa?->sortBy('nama')->values() ?? collect();

            // Ambil data absensi dengan eager loading
            $absensiData = collect();
            if ($kelas) {
                $absensiData = Absensi::query()
                    ->whereDate('tanggal', $tanggal)
                    ->whereIn('siswa_id', $siswa->pluck('id'))
                    ->get()
                    ->keyBy('siswa_id');
            }

            // Cek apakah hari libur
            $isLibur = $this->isHariLibur($tanggal);
            $hariLiburInfo = $this->getHariLiburInfo($tanggal);
            
            // Proses data untuk setiap siswa
            $rows = $siswa->map(function ($s) use ($absensiData, $isLibur, $hariLiburInfo) {
                $absensi = $absensiData->get($s->id);
                
                // Status dan keterangan
                $status = 'alpa';
                $keterangan = '';
                $jamDatang = null;
                $jamPulang = null;
                
                if ($isLibur) {
                    $status = 'libur';
                    $keterangan = $hariLiburInfo;
                } elseif ($absensi) {
                    $status = $absensi->status;
                    $keterangan = $absensi->keterangan;
                    $jamDatang = $absensi->jam_datang;
                    $jamPulang = $absensi->jam_pulang;
                }

                return [
                    'id' => $s->id,
                    'nis' => $s->nis,
                    'nama' => $s->nama,
                    'jenis_kelamin' => $s->jenis_kelamin,
                    'status' => $status,
                    'status_text' => $this->getStatusText($status),
                    'jam_datang' => $jamDatang,
                    'jam_pulang' => $jamPulang,
                    'keterangan' => $keterangan,
                    'barcode' => $s->barcode,
                ];
            })->all();

            // Hitung statistik
            $counts = collect($rows)->countBy('status')->all();
            $totalSiswa = count($rows);
            $hadir = $counts['hadir'] ?? 0;
            $izin = $counts['izin'] ?? 0;
            $sakit = $counts['sakit'] ?? 0;
            $alpa = $counts['alpa'] ?? 0;
            $libur = $counts['libur'] ?? 0;
            
            $persentaseKehadiran = ($totalSiswa > 0 && ($totalSiswa - $libur) > 0) ? 
                round((($hadir + $izin + $sakit) / ($totalSiswa - $libur)) * 100, 2) : 0;

            // Informasi sekolah
            $schoolName = Setting::getString('school.name', 'Sekolah');
            $schoolLogo = Setting::getString('school.logo_path', null);
            $semesterActive = Schema::hasTable('semesters')
                ? Semester::query()->where('is_active', true)->first()
                : null;

            $this->reportData = [
                'tanggal' => $tanggal,
                'tanggal_format' => CarbonImmutable::parse($tanggal)->locale('id')->translatedFormat('l, d F Y'),
                'kelas' => $kelas,
                'rows' => $rows,
                'counts' => $counts,
                'total_siswa' => $totalSiswa,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'libur' => $libur,
                'persentase_kehadiran' => $persentaseKehadiran,
                'school_name' => $schoolName,
                'school_logo_path' => $schoolLogo,
                'semester_active' => $semesterActive,
                'is_libur' => $isLibur,
                'hari_libur_info' => $hariLiburInfo,
            ];
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error loading report')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            $this->reportData = [];
        } finally {
            $this->isLoading = false;
        }
    }

    protected function isHariLibur($date): bool
    {
        $date = $date instanceof \Carbon\CarbonImmutable 
            ? $date 
            : \Carbon\CarbonImmutable::parse($date);
            
        // Cek apakah hari Minggu dan tidak diizinkan absen
        if ($date->isSunday() && !Setting::getBool('absensi.allow_sunday_attendance', false)) {
            return true;
        }
        
        // Cek apakah tanggal tersebut hari libur nasional
        return HariLibur::query()
            ->whereDate('tanggal', $date->toDateString())
            ->where('is_nasional', true)
            ->exists();
    }

    protected function getHariLiburInfo($date): string
    {
        $date = $date instanceof \Carbon\CarbonImmutable 
            ? $date 
            : \Carbon\CarbonImmutable::parse($date);
            
        // Cek apakah hari Minggu
        if ($date->isSunday() && !Setting::getBool('absensi.allow_sunday_attendance', false)) {
            return 'Hari Minggu';
        }
        
        // Cek apakah tanggal tersebut hari libur nasional
        $hariLibur = HariLibur::query()
            ->whereDate('tanggal', $date->toDateString())
            ->where('is_nasional', true)
            ->first();
            
        return $hariLibur?->keterangan ?? 'Hari Libur';
    }

    protected function getStatusText($status): string
    {
        return match($status) {
            'hadir' => 'Hadir',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpa' => 'Alpa',
            'libur' => 'Libur',
            default => 'Tidak Diketahui'
        };
    }

    public function exportToExcel()
    {
        if (empty($this->reportData)) {
            Notification::make()
                ->title('Tidak ada data untuk diekspor')
                ->warning()
                ->send();
            return;
        }

        // TODO: Implement Excel export
        Notification::make()
            ->title('Fitur export Excel akan segera hadir')
            ->info()
            ->send();
    }

    public function printReport()
    {
        if (empty($this->reportData)) {
            Notification::make()
                ->title('Tidak ada data untuk dicetak')
                ->warning()
                ->send();
            return;
        }

        // Prepare data for printing
        $printData = $this->reportData;
        
        // Store data in session for print view
        session(['print_laporan_harian' => $printData]);
        
        // Return JavaScript to open print window
        $this->js('window.open("' . route('print.laporan.harian') . '", "_blank", "width=800,height=600,scrollbars=yes,resizable=yes");');
        
        Notification::make()
            ->title('Membuka tampilan cetak...')
            ->info()
            ->send();
    }

    public function refreshReport()
    {
        $this->loadReport();
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\LiburSemester;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Semester;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Exports\LaporanBulananExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanBulanan extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Bulanan';
    protected static string $view = 'filament.pages.laporan-bulanan';

    public ?array $data = [];
    public $reportData = [];
    public $isLoading = false;

    public function mount(): void
    {
        $defaultKelas = Kelas::query()->orderBy('nama_kelas')->first();
        
        $this->form->fill([
            'bulan' => CarbonImmutable::now()->format('Y-m'),
            'kelas_id' => $defaultKelas?->id,
        ]);
        
        $this->loadReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->description('Pilih bulan dan kelas untuk melihat laporan bulanan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('bulan')
                                    ->label('Bulan')
                                    ->options(function () {
                                        $options = [];
                                        $currentDate = CarbonImmutable::now()->startOfYear();
                                        $endDate = CarbonImmutable::now()->addYear();
                                        
                                        while ($currentDate->lte($endDate)) {
                                            $options[$currentDate->format('Y-m')] = $currentDate->locale('id')->translatedFormat('F Y');
                                            $currentDate = $currentDate->addMonth();
                                        }
                                        
                                        return $options;
                                    })
                                    ->default(CarbonImmutable::now()->format('Y-m'))
                                    ->required()
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
            $bulan = $state['bulan'] ?? CarbonImmutable::now()->format('Y-m');
            $kelasId = $state['kelas_id'] ?? null;

            if (!$kelasId) {
                $this->reportData = [];
                $this->isLoading = false;
                return;
            }

            $tanggal = CarbonImmutable::createFromFormat('Y-m', $bulan);
            $firstDay = $tanggal->startOfMonth();
            $lastDay = $tanggal->endOfMonth();
            
            // Hitung total hari dalam bulan
            $period = CarbonPeriod::create($firstDay, $lastDay);
            $totalHari = $period->count();
            
            // Dapatkan daftar hari libur
            $hariLibur = $this->getHariLiburBulanan($firstDay, $lastDay);
            $totalLibur = count($hariLibur);
            $totalHariKerja = $totalHari - $totalLibur;
            
            // Ambil data kelas dan siswa
            $kelas = Kelas::query()->with('siswa')->find($kelasId);
            $siswa = $kelas?->siswa?->sortBy('nama')->values() ?? collect();
            
            // Ambil data absensi untuk bulan tersebut
            $absensiData = collect();
            if ($kelas) {
                $absensiData = Absensi::query()
                    ->whereDate('tanggal', '>=', $firstDay->toDateString())
                    ->whereDate('tanggal', '<=', $lastDay->toDateString())
                    ->whereIn('siswa_id', $siswa->pluck('id'))
                    ->orderBy('tanggal')
                    ->get()
                    ->groupBy(['siswa_id', function($item) {
                        return CarbonImmutable::parse($item->tanggal)->format('Y-m-d');
                    }]);
            }
            
            // Proses data untuk setiap siswa
            $rekapSiswa = $siswa->map(function ($s) use ($period, $absensiData, $hariLibur, $totalHariKerja) {
                $rekap = [
                    'id' => $s->id,
                    'nis' => $s->nis,
                    'nama' => $s->nama,
                    'jenis_kelamin' => $s->jenis_kelamin,
                    'hadir' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpa' => 0,
                    'libur' => 0,
                    'detail_harian' => [],
                    'persentase' => 0,
                ];
                
                foreach ($period as $date) {
                    $dateStr = $date->format('Y-m-d');
                    $isLibur = isset($hariLibur[$dateStr]);
                    
                    if ($isLibur) {
                        $rekap['detail_harian'][$dateStr] = [
                            'status' => 'libur',
                            'keterangan' => $hariLibur[$dateStr],
                            'is_libur' => true
                        ];
                        $rekap['libur']++;
                        continue;
                    }
                    
                    $absensiHari = $absensiData->get($s->id)?->get($dateStr)?->first();
                    
                    if ($absensiHari) {
                        $status = $absensiHari->status;
                        $rekap['detail_harian'][$dateStr] = [
                            'status' => $status,
                            'keterangan' => $absensiHari->keterangan,
                            'jam_datang' => $absensiHari->jam_datang,
                            'jam_pulang' => $absensiHari->jam_pulang,
                            'is_libur' => false
                        ];
                        
                        if (in_array($status, ['hadir', 'izin', 'sakit'])) {
                            $rekap[strtolower($status)]++;
                        } else {
                            $rekap['alpa']++;
                        }
                    } else {
                        $rekap['detail_harian'][$dateStr] = [
                            'status' => 'alpa',
                            'keterangan' => 'Tidak ada data absensi',
                            'is_libur' => false
                        ];
                        $rekap['alpa']++;
                    }
                }
                
                // Hitung persentase kehadiran (hanya hari kerja)
                if ($totalHariKerja > 0) {
                    $hadir = $rekap['hadir'] + $rekap['izin'] + $rekap['sakit'];
                    $rekap['persentase'] = round(($hadir / $totalHariKerja) * 100, 2);
                }
                
                return $rekap;
            });
            
            // Hitung statistik keseluruhan
            $totals = [
                'total_siswa' => $rekapSiswa->count(),
                'total_hadir' => $rekapSiswa->sum('hadir'),
                'total_izin' => $rekapSiswa->sum('izin'),
                'total_sakit' => $rekapSiswa->sum('sakit'),
                'total_alpa' => $rekapSiswa->sum('alpa'),
                'total_libur' => $rekapSiswa->sum('libur'),
                'avg_persentase' => $rekapSiswa->avg('persentase'),
            ];
            
            // Informasi sekolah
            $schoolName = Setting::getString('school.name', 'Sekolah');
            $schoolLogo = Setting::getString('school.logo_path', null);
            $schoolAddress = Setting::getString('school.address', '');
            $semesterActive = Schema::hasTable('semesters')
                ? Semester::query()->where('is_active', true)->first()
                : null;

            $this->reportData = [
                'bulan' => $bulan,
                'bulan_format' => CarbonImmutable::createFromFormat('Y-m', $bulan)->locale('id')->translatedFormat('F Y'),
                'kelas' => $kelas,
                'rekap_siswa' => $rekapSiswa,
                'totals' => $totals,
                'total_hari' => $totalHari,
                'total_libur' => $totalLibur,
                'total_hari_kerja' => $totalHariKerja,
                'hari_libur' => $hariLibur,
                'school_name' => $schoolName,
                'school_logo_path' => $schoolLogo,
                'school_address' => $schoolAddress,
                'semester_active' => $semesterActive,
                // Tambahkan statistik langsung untuk view
                'total_hadir' => $totals['total_hadir'],
                'total_izin' => $totals['total_izin'],
                'total_sakit' => $totals['total_sakit'],
                'total_alpa' => $totals['total_alpa'],
                'total_libur' => $totals['total_libur'],
                'persentase_kehadiran' => $totals['avg_persentase'],
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

    protected function getHariLiburBulanan($start, $end): array
    {
        $hariLibur = [];
        
        // Get all national holidays
        $liburNasional = HariLibur::query()
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->where('is_nasional', true)
            ->get();
        
        foreach ($liburNasional as $libur) {
            $hariLibur[\Carbon\Carbon::parse($libur->tanggal)->format('Y-m-d')] = $libur->keterangan;
        }
        
        // Get semester holidays that overlap with the month
        $liburSemester = LiburSemester::query()
            ->where('tanggal_mulai', '<=', $end->toDateString())
            ->where('tanggal_selesai', '>=', $start->toDateString())
            ->get();
        
        foreach ($liburSemester as $libur) {
            $period = CarbonPeriod::create(
                max($libur->tanggal_mulai, $start->toDateString()),
                min($libur->tanggal_selesai, $end->toDateString())
            );
            
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                if (!isset($hariLibur[$dateStr])) {
                    $hariLibur[$dateStr] = $libur->nama_libur;
                }
            }
        }
        
        // Add Sundays if not allowed
        if (!Setting::getBool('absensi.allow_sunday_attendance', false)) {
            $period = CarbonPeriod::create($start, $end);
            foreach ($period as $date) {
                if ($date->isSunday()) {
                    $dateStr = $date->format('Y-m-d');
                    if (!isset($hariLibur[$dateStr])) {
                        $hariLibur[$dateStr] = 'Hari Minggu';
                    }
                }
            }
        }
        
        return $hariLibur;
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

        $exportData = json_decode(json_encode($this->reportData), true);
        $filename = 'Laporan Bulanan - '.($exportData['bulan_format'] ?? 'Bulan').'.xlsx';

        return Excel::download(new LaporanBulananExport($exportData), $filename);
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
        
        // Debug: Check if detail_harian exists in the data
        \Log::info('Backend Debug - Before Session:', [
            'has_rekap_siswa' => isset($printData['rekap_siswa']),
            'rekap_siswa_count' => isset($printData['rekap_siswa']) ? count($printData['rekap_siswa']) : 0,
            'first_student_name' => isset($printData['rekap_siswa'][0]) ? $printData['rekap_siswa'][0]['nama'] ?? 'N/A' : 'N/A',
            'first_student_has_detail_harian' => isset($printData['rekap_siswa'][0]['detail_harian']),
            'detail_harian_count' => isset($printData['rekap_siswa'][0]['detail_harian']) ? count($printData['rekap_siswa'][0]['detail_harian']) : 0,
            'sample_detail_data' => isset($printData['rekap_siswa'][0]['detail_harian']) ? 
                array_slice($printData['rekap_siswa'][0]['detail_harian'], 0, 3, true) : []
        ]);
        
        // Store data in session for print view
        // Use JSON encode/decode to ensure proper serialization
        $sessionData = json_decode(json_encode($printData), true);
        session(['print_laporan_bulanan' => $sessionData]);
        
        // Return JavaScript to open print window
        $this->js('window.open("' . route('print.laporan.bulanan') . '", "_blank", "width=800,height=600,scrollbars=yes,resizable=yes");');
        
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

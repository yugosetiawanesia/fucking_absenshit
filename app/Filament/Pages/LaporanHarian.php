<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class LaporanHarian extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Harian';

    protected static string $view = 'filament.pages.laporan-harian';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tanggal' => CarbonImmutable::now()->toDateString(),
            'kelas_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\DatePicker::make('tanggal')
                    ->required(),
                \Filament\Forms\Components\Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn () => Kelas::query()->orderBy('nama_kelas')->pluck('nama_kelas', 'id')->all())
                    ->searchable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function getReportData(): array
    {
        $state = $this->form->getState();
        $tanggal = (string) ($state['tanggal'] ?? CarbonImmutable::now()->toDateString());
        $kelasId = $state['kelas_id'] ?? null;

        $kelas = $kelasId ? Kelas::query()->with('siswa')->find($kelasId) : null;

        $siswa = $kelas?->siswa?->sortBy('nama')->values() ?? collect();

        $absensiBySiswaId = collect();
        if ($kelas) {
            $absensiBySiswaId = \App\Models\Absensi::query()
                ->whereDate('tanggal', $tanggal)
                ->whereIn('siswa_id', $siswa->pluck('id'))
                ->get()
                ->keyBy('siswa_id');
        }

        $rows = $siswa->map(function ($s) use ($absensiBySiswaId) {
            $a = $absensiBySiswaId->get($s->id);
            $status = $a?->status ?? 'alpa';

            return [
                'nis' => $s->nis,
                'nama' => $s->nama,
                'jenis_kelamin' => $s->jenis_kelamin,
                'status' => $status,
                'jam_datang' => $a?->jam_datang,
                'jam_pulang' => $a?->jam_pulang,
                'keterangan' => $a?->keterangan,
            ];
        })->all();

        $counts = collect($rows)->countBy('status')->all();

        $schoolName = Setting::getString('school.name', '');
        $schoolLogo = Setting::getString('school.logo_path', null);
        $semesterActive = Schema::hasTable('semesters')
            ? (\App\Models\Semester::query()->where('is_active', true)->first())
            : null;

        return [
            'tanggal' => $tanggal,
            'kelas' => $kelas,
            'rows' => $rows,
            'counts' => $counts,
            'school_name' => $schoolName,
            'school_logo_path' => $schoolLogo,
            'semester_active' => $semesterActive,
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class LaporanBulanan extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Bulanan';

    protected static string $view = 'filament.pages.laporan-bulanan';

    public ?array $data = [];

    public function mount(): void
    {
        $now = CarbonImmutable::now();

        $this->form->fill([
            'bulan' => $now->format('Y-m'),
            'kelas_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make('bulan')
                    ->label('Bulan (YYYY-MM)')
                    ->placeholder('2025-12')
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
        $bulan = (string) ($state['bulan'] ?? CarbonImmutable::now()->format('Y-m'));
        $kelasId = $state['kelas_id'] ?? null;

        $firstDay = CarbonImmutable::createFromFormat('Y-m', $bulan, config('app.timezone', 'UTC'))->startOfMonth();
        $lastDay = $firstDay->endOfMonth();
        $days = [];
        for ($d = $firstDay; $d->lte($lastDay); $d = $d->addDay()) {
            $days[] = $d;
        }

        $kelas = $kelasId ? Kelas::query()->with('siswa')->find($kelasId) : null;
        $siswa = $kelas?->siswa?->sortBy('nama')->values() ?? collect();

        $absensi = collect();
        if ($kelas) {
            $absensi = \App\Models\Absensi::query()
                ->whereBetween('tanggal', [$firstDay->toDateString(), $lastDay->toDateString()])
                ->whereIn('siswa_id', $siswa->pluck('id'))
                ->get();
        }

        $absensiMap = $absensi
            ->groupBy('siswa_id')
            ->map(fn ($items) => $items->keyBy(fn ($a) => $a->tanggal?->toDateString() ?? (string) $a->tanggal));

        $rows = $siswa->map(function ($s) use ($days, $absensiMap) {
            $perDay = [];
            $totals = [
                'hadir' => 0,
                'sakit' => 0,
                'izin' => 0,
                'alpa' => 0,
            ];

            foreach ($days as $day) {
                $dateKey = $day->toDateString();
                $a = $absensiMap->get($s->id)?->get($dateKey);
                $status = $a?->status ?? 'alpa';

                if (isset($totals[$status])) {
                    $totals[$status]++;
                }

                $perDay[$dateKey] = $status;
            }

            return [
                'nis' => $s->nis,
                'nama' => $s->nama,
                'jenis_kelamin' => $s->jenis_kelamin,
                'per_day' => $perDay,
                'totals' => $totals,
            ];
        })->all();

        $genderCounts = $siswa->countBy('jenis_kelamin')->all();

        $schoolName = Setting::getString('school.name', '');
        $schoolLogo = Setting::getString('school.logo_path', null);
        $semesterActive = Schema::hasTable('semesters')
            ? (\App\Models\Semester::query()->where('is_active', true)->first())
            : null;

        return [
            'bulan' => $bulan,
            'first_day' => $firstDay,
            'days' => $days,
            'kelas' => $kelas,
            'rows' => $rows,
            'gender_counts' => $genderCounts,
            'school_name' => $schoolName,
            'school_logo_path' => $schoolLogo,
            'semester_active' => $semesterActive,
        ];
    }
}

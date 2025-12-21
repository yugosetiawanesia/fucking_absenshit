<x-filament-panels::page>
    <div class="space-y-4">
        <div class="print:hidden">
            {{ $this->form }}

            <div class="mt-4 flex gap-2">
                <x-filament::button type="button" onclick="window.print()">
                    Print
                </x-filament::button>
            </div>
        </div>

        @php($report = $this->getReportData())

        <div class="bg-white p-6 print:p-0 print:shadow-none">
            <div class="flex items-start justify-between gap-4">
                <div class="w-24">
                    @if($report['school_logo_path'])
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($report['school_logo_path']) }}" alt="Logo" class="h-20 w-20 object-contain" />
                    @endif
                </div>
                <div class="flex-1 text-center">
                    <div class="text-xl font-bold">DAFTAR HADIR SISWA</div>
                    @if($report['school_name'])
                        <div class="font-semibold">{{ $report['school_name'] }}</div>
                    @endif
                    @if($report['semester_active'])
                        <div class="text-sm font-semibold">TAHUN PELAJARAN {{ $report['semester_active']->tahun_ajaran }}</div>
                    @endif
                </div>
                <div class="w-24"></div>
            </div>

            <div class="mt-4 flex justify-between text-sm">
                <div>
                    <span class="font-semibold">Bulan:</span>
                    {{ $report['first_day']->translatedFormat('F') }}
                </div>
                <div>
                    <span class="font-semibold">Kelas:</span>
                    {{ $report['kelas']?->nama_kelas ?? '-' }}
                </div>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full border-collapse border text-[10px]">
                    <thead>
                        <tr>
                            <th class="border px-2 py-1" rowspan="2">No</th>
                            <th class="border px-2 py-1" rowspan="2">Nama</th>
                            <th class="border px-2 py-1 text-center" colspan="{{ count($report['days']) }}">Hari/Tanggal</th>
                            <th class="border px-2 py-1" colspan="4">Total</th>
                        </tr>
                        <tr>
                            @foreach($report['days'] as $day)
                                <th class="border px-1 py-1 text-center w-7">
                                    {{ $day->format('d') }}
                                </th>
                            @endforeach
                            <th class="border px-2 py-1 text-center">H</th>
                            <th class="border px-2 py-1 text-center">S</th>
                            <th class="border px-2 py-1 text-center">I</th>
                            <th class="border px-2 py-1 text-center">A</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $i => $row)
                            <tr>
                                <td class="border px-2 py-1 text-center">{{ $i + 1 }}</td>
                                <td class="border px-2 py-1 whitespace-nowrap">{{ $row['nama'] }}</td>
                                @foreach($report['days'] as $day)
                                    @php($key = $day->toDateString())
                                    @php($status = $row['per_day'][$key] ?? 'alpa')
                                    @php($letter = $status === 'hadir' ? 'H' : ($status === 'sakit' ? 'S' : ($status === 'izin' ? 'I' : ($status === 'libur' ? 'L' : 'A'))))
                                    <td class="border px-1 py-1 text-center">{{ $letter }}</td>
                                @endforeach
                                <td class="border px-2 py-1 text-center">{{ $row['totals']['hadir'] ?? 0 }}</td>
                                <td class="border px-2 py-1 text-center">{{ $row['totals']['sakit'] ?? 0 }}</td>
                                <td class="border px-2 py-1 text-center">{{ $row['totals']['izin'] ?? 0 }}</td>
                                <td class="border px-2 py-1 text-center">{{ $row['totals']['alpa'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-sm">
                <div>Jumlah siswa: {{ count($report['rows']) }}</div>
                <div>Laki-laki: {{ $report['gender_counts']['L'] ?? 0 }}</div>
                <div>Perempuan: {{ $report['gender_counts']['P'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .fi-main { padding: 0 !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</x-filament-panels::page>

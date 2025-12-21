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
                    <span class="font-semibold">Tanggal:</span>
                    {{ $report['tanggal'] }}
                </div>
                <div>
                    <span class="font-semibold">Kelas:</span>
                    {{ $report['kelas']?->nama_kelas ?? '-' }}
                </div>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full border-collapse border text-xs">
                    <thead>
                        <tr>
                            <th class="border px-2 py-1">No</th>
                            <th class="border px-2 py-1">NIS</th>
                            <th class="border px-2 py-1">Nama</th>
                            <th class="border px-2 py-1">Status</th>
                            <th class="border px-2 py-1">Jam datang</th>
                            <th class="border px-2 py-1">Jam pulang</th>
                            <th class="border px-2 py-1">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $i => $row)
                            <tr>
                                <td class="border px-2 py-1 text-center">{{ $i + 1 }}</td>
                                <td class="border px-2 py-1">{{ $row['nis'] }}</td>
                                <td class="border px-2 py-1">{{ $row['nama'] }}</td>
                                <td class="border px-2 py-1 text-center">{{ $row['status'] }}</td>
                                <td class="border px-2 py-1 text-center">{{ $row['jam_datang'] ?? '-' }}</td>
                                <td class="border px-2 py-1 text-center">{{ $row['jam_pulang'] ?? '-' }}</td>
                                <td class="border px-2 py-1">{{ $row['keterangan'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-sm">
                <div class="font-semibold">Ringkasan</div>
                <div>Hadir: {{ $report['counts']['hadir'] ?? 0 }}</div>
                <div>Izin: {{ $report['counts']['izin'] ?? 0 }}</div>
                <div>Sakit: {{ $report['counts']['sakit'] ?? 0 }}</div>
                <div>Alpa: {{ $report['counts']['alpa'] ?? 0 }}</div>
                <div>Libur: {{ $report['counts']['libur'] ?? 0 }}</div>
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

<x-filament-panels::page>
    <x-filament-panels::form wire:submit="loadData">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{ $this->form }}
        </div>
        
        <div class="mt-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">
                    Rekap Absensi Kelas {{ \App\Models\Kelas::find($kelasId)?->nama_kelas ?? 'Tidak Diketahui' }}
                    - {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
                </h2>
                <div class="flex space-x-2">
                    <x-filament::button type="button" icon="heroicon-o-printer" 
                        wire:click="printPdf" color="gray">
                        Cetak PDF
                    </x-filament::button>
                </div>
            </div>
            
            <!-- Ringkasan -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm font-medium text-gray-500">Total Hari</div>
                    <div class="mt-1 text-2xl font-semibold">{{ $totalHari }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm font-medium text-gray-500">Hari Kerja</div>
                    <div class="mt-1 text-2xl font-semibold text-green-600">{{ $totalHariKerja }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm font-medium text-gray-500">Hari Libur</div>
                    <div class="mt-1 text-2xl font-semibold text-purple-600">{{ $totalLibur }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm font-medium text-gray-500">Jumlah Siswa</div>
                    <div class="mt-1 text-2xl font-semibold">{{ count($rekapSiswa) }}</div>
                </div>
            </div>
            
            <!-- Tabel Rekap -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th rowspan="2" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th rowspan="2" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS</th>
                                <th rowspan="2" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                                <th colspan="4" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Jumlah</th>
                                <th rowspan="2" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Persentase</th>
                            </tr>
                            <tr>
                                <th class="px-4 py-2 text-center text-xs font-medium text-green-600 uppercase tracking-wider">Hadir</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-blue-600 uppercase tracking-wider">Izin</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-yellow-600 uppercase tracking-wider">Sakit</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-red-600 uppercase tracking-wider">Alfa</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rekapSiswa as $index => $siswa)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ $siswa['nis'] }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $siswa['nama'] }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-green-600">
                                        {{ $siswa['hadir'] }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-blue-600">
                                        {{ $siswa['izin'] }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-yellow-600">
                                        {{ $siswa['sakit'] }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-red-600">
                                        {{ $siswa['alpa'] }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $siswa['persentase'] >= 80 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $siswa['persentase'] }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Tidak ada data siswa dalam kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Keterangan -->
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Keterangan:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div class="flex items-center">
                        <span class="inline-block w-4 h-4 bg-green-100 text-green-800 text-xs text-center rounded-full mr-2">H</span>
                        <span>Hadir</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-4 h-4 bg-blue-100 text-blue-800 text-xs text-center rounded-full mr-2">I</span>
                        <span>Izin</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-4 h-4 bg-yellow-100 text-yellow-800 text-xs text-center rounded-full mr-2">S</span>
                        <span>Sakit</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-4 h-4 bg-red-100 text-red-800 text-xs text-center rounded-full mr-2">A</span>
                        <span>Alfa</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-4 h-4 bg-purple-100 text-purple-800 text-xs text-center rounded-full mr-2">L</span>
                        <span>Libur</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament-panels::form>
    
    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                // Auto refresh saat form berubah
                Livewire.on('refreshBulanan', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        </script>
    @endpush
</x-filament-panels::page>

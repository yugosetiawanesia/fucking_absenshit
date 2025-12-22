<x-filament-panels::page>
    <x-filament-panels::form wire:submit="loadData">
        {{ $this->form }}
        
        <div class="mt-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">
                    Daftar Kehadiran Kelas {{ \App\Models\Kelas::find($kelasId)?->nama_kelas ?? 'Tidak Diketahui' }}
                </h2>
                <div class="flex space-x-2">
                    <x-filament::button type="button" icon="heroicon-o-printer" 
                        wire:click="printPdf" color="gray">
                        Cetak PDF
                    </x-filament::button>
                </div>
            </div>
            
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Datang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pulang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($siswaList as $index => $siswa)
                            <tr @class([
                                'bg-gray-50' => $index % 2 === 0,
                                'bg-white' => $index % 2 !== 0,
                            ])>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $siswa['nis'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $siswa['nama'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColor = match($siswa['status']) {
                                            'Hadir' => 'bg-green-100 text-green-800',
                                            'Izin' => 'bg-blue-100 text-blue-800',
                                            'Sakit' => 'bg-yellow-100 text-yellow-800',
                                            'Libur' => 'bg-purple-100 text-purple-800',
                                            default => 'bg-red-100 text-red-800'
                                        };
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                        {{ $siswa['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $siswa['jam_datang'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $siswa['jam_pulang'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $siswa['keterangan'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data siswa dalam kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-sm text-gray-500">
                <p>Total: {{ count($siswaList) }} siswa</p>
            </div>
        </div>
    </x-filament-panels::form>
    
    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                // Auto refresh saat form berubah
                Livewire.on('refreshHarian', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        </script>
    @endpush
</x-filament-panels::page>

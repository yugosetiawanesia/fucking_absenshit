<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Filter -->
        <x-filament-panels::form wire:submit="loadReport">
            {{ $this->form }}
        </x-filament-panels::form>

        <!-- Loading State -->
        @if($isLoading)
            <div class="flex justify-center items-center py-12">
                <x-filament::loading-indicator class="w-8 h-8" />
            </div>
        @endif

        <!-- Report Content -->
        @if(!empty($reportData) && !$isLoading)
            <!-- Header Information -->
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $reportData['school_name'] }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-300 mt-1">
                            Laporan Harian - {{ $reportData['tanggal_format'] }}
                        </p>
                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-200">
                            <span class="font-medium">Kelas :</span> {{ $reportData['kelas']['nama_kelas'] }}
                            <span class="mx-2">|</span>
                            @if($reportData['semester_active'])
                                <span class="font-medium">Semester :</span> {{ ucfirst($reportData['semester_active']['semester']) }} {{ $reportData['semester_active']['tahun_ajaran'] }}
                                <span class="mx-2">|</span>
                            @endif
                            <span class="font-medium">Total Siswa :</span> {{ $reportData['total_siswa'] ?? count($reportData['rows'] ?? []) }}
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <x-filament::button 
                            wire:click="refreshReport" 
                            icon="heroicon-o-arrow-path"
                            color="gray"
                            size="sm">
                            Refresh
                        </x-filament::button>
                        <x-filament::button 
                            wire:click="exportToExcel" 
                            icon="heroicon-o-document-arrow-down"
                            color="success"
                            size="sm">
                            Excel
                        </x-filament::button>
                        <x-filament::button 
                            wire:click="exportToPdf" 
                            icon="heroicon-o-document-text"
                            color="danger"
                            size="sm">
                            PDF
                        </x-filament::button>
                        <x-filament::button 
                            wire:click="printReport" 
                            icon="heroicon-o-printer"
                            color="primary"
                            size="sm">
                            Cetak
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-col space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Ringkasan Laporan</h3>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-green-200 dark:border-green-900/40 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Persentase</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($reportData['persentase_kehadiran'], 1) }}%</p>
                        </div>
                        <div class="p-2 bg-green-100 dark:bg-green-900/40 rounded-lg">
                            <x-filament::icon icon="heroicon-o-chart-pie" class="w-5 h-5 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Siswa</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $reportData['total_siswa'] }}</p>
                        </div>
                        <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <x-filament::icon icon="heroicon-o-users" class="w-5 h-5 text-gray-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-green-200 dark:border-green-900/40 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Hadir</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ $reportData['hadir'] }}</p>
                        </div>
                        <div class="p-2 bg-green-100 dark:bg-green-900/40 rounded-lg">
                            <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-blue-200 dark:border-blue-900/40 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Izin</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $reportData['izin'] }}</p>
                        </div>
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
                            <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-yellow-200 dark:border-yellow-900/40 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">Sakit</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $reportData['sakit'] }}</p>
                        </div>
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900/40 rounded-lg">
                            <x-filament::icon icon="heroicon-o-heart" class="w-5 h-5 text-yellow-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-red-200 dark:border-red-900/40 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-red-600 uppercase tracking-wide">Alpa</p>
                            <p class="text-2xl font-bold text-red-600 mt-1">{{ $reportData['alpa'] }}</p>
                        </div>
                        <div class="p-2 bg-red-100 dark:bg-red-900/40 rounded-lg">
                            <x-filament::icon icon="heroicon-o-x-circle" class="w-5 h-5 text-red-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-purple-200 dark:border-purple-900/40 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-purple-600 uppercase tracking-wide">Libur</p>
                            <p class="text-2xl font-bold text-purple-600 mt-1">{{ $reportData['libur'] }}</p>
                        </div>
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/40 rounded-lg">
                            <x-filament::icon icon="heroicon-o-calendar" class="w-5 h-5 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Daftar Kehadiran Siswa</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gender</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jam Datang</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jam Pulang</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($reportData['rows'] as $index => $siswa)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $siswa['nis'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $siswa['nama'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @php
                                            $genderIcon = $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P';
                                            $genderColor = $siswa['jenis_kelamin'] === 'L' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' : 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-200';
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $genderColor }}">
                                            {{ $genderIcon }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @php
                                            $statusColors = [
                                                'hadir' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                                'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                                'sakit' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                                                'alpa' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                                'libur' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200'
                                            ];
                                            $statusColor = $statusColors[$siswa['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
                                        @endphp
                                        <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                            {{ $siswa['status_text'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $siswa['jam_datang'] ? \Carbon\Carbon::parse($siswa['jam_datang'])->format('H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $siswa['jam_pulang'] ? \Carbon\Carbon::parse($siswa['jam_pulang'])->format('H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $siswa['keterangan'] ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada data siswa dalam kelas ini
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @endif
    </div>

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

<x-filament-panels::page>
    <!-- Logo Upload Section (Terpisah dengan Livewire Native) -->
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Logo Sekolah</h3>
                        @if($currentLogoPath)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Path: {{ $currentLogoPath }}
                            </p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if($currentLogoPath)
                            <button 
                                wire:click="testStorageLink" 
                                type="button"
                                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Test
                            </button>
                            <button 
                                wire:click="$refresh" 
                                type="button"
                                class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Refresh
                            </button>
                            <button 
                                wire:click="deleteLogo" 
                                type="button"
                                wire:confirm="Yakin ingin menghapus logo?"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Hapus
                            </button>
                        @endif
                    </div>
                </div>
                
                @if($currentLogoPath && Storage::disk('public')->exists($currentLogoPath))
                    <div class="flex justify-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div class="relative">
                            @php
                                $logoUrl = $this->getLogoUrl();
                                // Alternative URLs untuk fallback
                                $altUrl1 = url('storage/' . $currentLogoPath);
                                $altUrl2 = asset('storage/' . $currentLogoPath);
                            @endphp
                            
                            <img 
                                src="{{ $logoUrl }}" 
                                alt="Logo Sekolah" 
                                class="w-32 h-32 object-contain rounded-lg"
                                onerror="console.error('Failed to load:', this.src); 
                                         if(this.src !== '{{ $altUrl1 }}') { 
                                             console.log('Trying alternative URL 1:', '{{ $altUrl1 }}'); 
                                             this.src = '{{ $altUrl1 }}'; 
                                         } else if(this.src !== '{{ $altUrl2 }}') { 
                                             console.log('Trying alternative URL 2:', '{{ $altUrl2 }}'); 
                                             this.src = '{{ $altUrl2 }}'; 
                                         } else { 
                                             this.style.display='none'; 
                                             this.nextElementSibling.style.display='block'; 
                                         }">
                            <div class="w-32 h-32 border-2 border-dashed border-red-300 rounded-lg items-center justify-center text-center p-4 hidden">
                                <svg class="w-8 h-8 mx-auto mb-1 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-xs text-red-600">Logo gagal dimuat</p>
                            </div>
                            <div class="absolute inset-0 border-2 border-gray-300 dark:border-gray-600 rounded-lg pointer-events-none"></div>
                        </div>
                    </div>
                    
                    <!-- Debug Info (opsional, bisa dihapus jika sudah tidak diperlukan) -->
                    <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-xs">
                        <details>
                            <summary class="cursor-pointer text-blue-700 dark:text-blue-300 text-xs">Debug Info</summary>
                            <div class="mt-2 space-y-1 text-gray-600 dark:text-gray-400 font-mono text-[10px]">
                                <div>Path: {{ $currentLogoPath }}</div>
                                <div class="truncate">URL: {{ $logoUrl }}</div>
                                <div>Exists: {{ Storage::disk('public')->exists($currentLogoPath) ? '✓' : '✗' }}</div>
                            </div>
                        </details>
                    </div>
                @else
                    <div class="flex justify-center p-4">
                        <div class="w-32 h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center">
                            <div class="text-center text-gray-400 dark:text-gray-500">
                                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs">Belum ada logo</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Upload Logo Baru
                        </label>
                        <input 
                            type="file" 
                            wire:model="newLogo" 
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="block w-full text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Format: JPG/PNG/WEBP, maksimal 1MB, disarankan rasio 1:1
                        </p>
                    </div>
                    
                    @error('newLogo')
                        <div class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </div>
                    @enderror

                    <div wire:loading wire:target="newLogo" class="text-sm text-blue-600 dark:text-blue-400">
                        <svg class="animate-spin inline w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses file...
                    </div>

                    @if($newLogo)
                        <button 
                            wire:click="uploadLogo" 
                            type="button"
                            wire:loading.attr="disabled"
                            wire:target="uploadLogo"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <svg wire:loading.remove wire:target="uploadLogo" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <svg wire:loading wire:target="uploadLogo" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="uploadLogo">Upload Logo</span>
                            <span wire:loading wire:target="uploadLogo">Mengupload...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    
    <!-- Form Pengaturan Lainnya -->
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-start gap-2 mt-6">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Simpan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
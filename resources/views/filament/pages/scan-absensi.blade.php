<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Scan Barcode / QR</h2>
                <div class="text-xs text-gray-500 dark:text-gray-400">PC & Mobile</div>
            </div>

            <div class="mt-4">
                <label class="text-sm text-gray-700 dark:text-gray-200">Mode</label>
                <select
                    class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    wire:model.live="mode"
                >
                    <option value="auto">Auto (Masuk lalu Pulang)</option>
                    <option value="masuk">Masuk saja</option>
                    <option value="pulang">Pulang saja</option>
                </select>
            </div>

            <div class="mt-3">
                <div id="sound-status" class="text-xs text-gray-500 dark:text-gray-400">Suara: memuat…</div>
            </div>

            <div class="mt-4 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Jika manual bisa masukkan kode</div>
                <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="w-full">
                        <label class="text-xs text-gray-600 dark:text-gray-300">Kode Barcode</label>
                        <input
                            type="text"
                            class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                            placeholder="Contoh: SMPN1PIANI-444546"
                            wire:model.live="manualBarcode"
                            wire:keydown.enter="submitManualScan"
                        />
                    </div>
                    <div class="shrink-0">
                        <x-filament::button type="button" wire:click="submitManualScan">Proses</x-filament::button>
                    </div>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gunakan ini untuk memastikan proses absensi berjalan walau kamera belum terbaca.</div>
            </div>

            <div class="mt-4">
                <video id="zxing-video" style="width: 100%; border-radius: 12px;"></video>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Arahkan kamera ke barcode pada kartu. Pastikan pencahayaan cukup dan barcode terlihat jelas.
                </div>
                <div id="scan-status" class="mt-2 text-xs text-gray-600 dark:text-gray-300">Status: memuat scanner…</div>
                <div id="scan-last" class="mt-1 text-xs text-gray-600 dark:text-gray-300"></div>
            </div>

            <div class="mt-4">
                <x-filament::button type="button" color="gray" id="btn-stop-scan">Stop Kamera</x-filament::button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hasil Scan Terakhir</h2>

            @if($this->lastScan)
                <div class="mt-4 grid grid-cols-1 gap-3 text-sm">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ data_get($this->lastScan, 'siswa.nama') }}</div>
                        <div class="text-gray-600 dark:text-gray-300">NIS: {{ data_get($this->lastScan, 'siswa.nis') }}</div>
                        <div class="text-gray-600 dark:text-gray-300">Kelas: {{ data_get($this->lastScan, 'siswa.kelas') }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <div class="text-gray-600 dark:text-gray-300">Tanggal: <span class="text-gray-900 dark:text-gray-100">{{ data_get($this->lastScan, 'tanggal') }}</span></div>
                        <div class="text-gray-600 dark:text-gray-300">Datang: <span class="text-gray-900 dark:text-gray-100">{{ data_get($this->lastScan, 'jam_datang') ?? '-' }}</span></div>
                        <div class="text-gray-600 dark:text-gray-300">Pulang: <span class="text-gray-900 dark:text-gray-100">{{ data_get($this->lastScan, 'jam_pulang') ?? '-' }}</span></div>
                        <div class="text-gray-600 dark:text-gray-300">Status: <span class="text-gray-900 dark:text-gray-100">{{ data_get($this->lastScan, 'status') }}</span></div>
                        <div class="text-gray-600 dark:text-gray-300">Keterangan: <span class="text-gray-900 dark:text-gray-100">{{ data_get($this->lastScan, 'keterangan') ?? '-' }}</span></div>
                    </div>
                </div>
            @else
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">Belum ada hasil scan.</div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.20.0/umd/index.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let lastDecodedText = null;
            let lastDecodedAt = 0;

            const statusEl = document.getElementById('scan-status');
            const lastEl = document.getElementById('scan-last');
            const soundStatusEl = document.getElementById('sound-status');
            const setStatus = (text) => {
                if (statusEl) statusEl.textContent = `Status: ${text}`;
            };

            const successAudio = new Audio(@json(asset('sounds/beep.mp3')));
            successAudio.preload = 'auto';
            successAudio.volume = 1;

            const errorAudio = new Audio(@json(asset('sounds/error.mp3')));
            errorAudio.preload = 'auto';
            errorAudio.volume = 1;

            const playSound = async (audio) => {
                try {
                    audio.currentTime = 0;
                    await audio.play();
                    if (soundStatusEl) soundStatusEl.textContent = 'Suara: aktif';
                } catch (e) {
                    if (soundStatusEl) soundStatusEl.textContent = 'Suara: diblokir browser (klik sekali di halaman)';
                }
            };

            const unlockAudio = async () => {
                try {
                    // play() singkat untuk unlock policy, langsung pause.
                    await successAudio.play();
                    successAudio.pause();
                    successAudio.currentTime = 0;
                    if (soundStatusEl) soundStatusEl.textContent = 'Suara: aktif';
                } catch (e) {
                    if (soundStatusEl) soundStatusEl.textContent = 'Suara: diblokir browser (klik sekali di halaman)';
                }
            };

            unlockAudio();

            const unlockOnFirstGesture = async () => {
                await unlockAudio();
                document.removeEventListener('pointerdown', unlockOnFirstGesture, true);
                document.removeEventListener('keydown', unlockOnFirstGesture, true);
            };
            document.addEventListener('pointerdown', unlockOnFirstGesture, true);
            document.addEventListener('keydown', unlockOnFirstGesture, true);

            document.addEventListener('livewire:init', () => {
                if (!window.Livewire) return;
                Livewire.on('scan-feedback', (payload) => {
                    const type = payload?.type;
                    if (type === 'success') {
                        playSound(successAudio);
                    } else if (type === 'error') {
                        playSound(errorAudio);
                    }
                });
            });

            const start = async () => {
                if (!window.ZXing) {
                    setTimeout(() => start(), 200);
                    return;
                }

                setStatus('inisialisasi kamera…');

                const hints = new Map();
                hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.QR_CODE,
                ]);

                const codeReader = new ZXing.BrowserMultiFormatReader(hints);
                window.__zxingReader = codeReader;

                try {
                    const videoElement = document.getElementById('zxing-video');
                    setStatus('kamera aktif, menunggu barcode…');
                    await codeReader.decodeFromVideoDevice(
                        undefined,
                        videoElement,
                        (result, err) => {
                            if (!result) {
                                return;
                            }

                            const decodedText = result.getText();
                            const now = Date.now();
                            if (decodedText === lastDecodedText && now - lastDecodedAt < 2500) {
                                return;
                            }

                            lastDecodedText = decodedText;
                            lastDecodedAt = now;

                            if (lastEl) {
                                lastEl.textContent = `Terbaca: ${decodedText}`;
                            }

                            $wire.scanBarcode(decodedText);
                        },
                    );
                } catch (e) {
                    setStatus('gagal mengakses kamera. Pastikan izin kamera di-allow dan gunakan Chrome/Edge.');
                }
            };

            start();

            const stopBtn = document.getElementById('btn-stop-scan');
            if (stopBtn) {
                stopBtn.addEventListener('click', async () => {
                    try {
                        const reader = window.__zxingReader;
                        if (reader) {
                            reader.reset();
                        }
                    } catch (e) {}
                });
            }
        });
    </script>
</x-filament-panels::page>

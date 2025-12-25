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

            <div class="mt-4">
                <label class="text-sm text-gray-700 dark:text-gray-200">Pilih Kamera</label>
                <select
                    id="camera-select"
                    class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    wire:change="restartScanner"
                >
                    <option value="">Memuat kamera...</option>
                </select>
            </div>

            <div class="mt-3">
                <div id="sound-status" class="text-xs text-gray-500 dark:text-gray-400">Suara: memuat…</div>
            </div>

            <div class="mt-4 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Debug Scanner</div>
                <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                    <div>Barcode yang valid: <code class="bg-gray-100 px-1 rounded">SMPN1PIANI-34655475</code></div>
                    <div class="mt-1">Format: CODE_128, panjang 19 karakter</div>
                    <div class="mt-1">Status scanner: <span id="debug-status" class="font-mono">menunggu...</span></div>
                    <div class="mt-1">Hasil scan: <span id="debug-result" class="font-mono text-green-600">-</span></div>
                    <div class="mt-1">ZXing Library: <span id="zxing-status" class="font-mono">memuat...</span></div>
                    <div class="mt-1">Kamera detected: <span id="camera-status" class="font-mono">menunggu...</span></div>
                    <div class="mt-1">Browser info: <span id="browser-info" class="font-mono">-</span></div>
                    <div class="mt-1">HTTPS status: <span id="https-status" class="font-mono">-</span></div>
                </div>
                <div class="mt-2">
                    <button type="button" onclick="testScanner()" class="text-xs bg-blue-500 text-white px-2 py-1 rounded">Test Scanner</button>
                    <button type="button" onclick="clearDebug()" class="text-xs bg-gray-500 text-white px-2 py-1 rounded ml-1">Clear</button>
                    <button type="button" onclick="testZXing()" class="text-xs bg-purple-500 text-white px-2 py-1 rounded ml-1">Test ZXing</button>
                    <button type="button" onclick="testCamera()" class="text-xs bg-green-500 text-white px-2 py-1 rounded ml-1">Test Camera</button>
                    <button type="button" onclick="testBrowser()" class="text-xs bg-orange-500 text-white px-2 py-1 rounded ml-1">Test Browser</button>
                </div>
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
            const debugStatusEl = document.getElementById('debug-status');
            const debugResultEl = document.getElementById('debug-result');
            const zxingStatusEl = document.getElementById('zxing-status');
            const cameraStatusEl = document.getElementById('camera-status');
            const browserInfoEl = document.getElementById('browser-info');
            const httpsStatusEl = document.getElementById('https-status');
            
            const setStatus = (text) => {
                if (statusEl) statusEl.textContent = `Status: ${text}`;
                if (debugStatusEl) debugStatusEl.textContent = text;
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

            // Debug functions
            window.testScanner = () => {
                if (debugResultEl) {
                    debugResultEl.textContent = 'SMPN1PIANI-34655475 (test)';
                    debugResultEl.className = 'font-mono text-blue-600';
                }
                console.log('Test scanner dengan barcode: SMPN1PIANI-34655475');
            };

            window.clearDebug = () => {
                if (debugResultEl) {
                    debugResultEl.textContent = '-';
                    debugResultEl.className = 'font-mono text-green-600';
                }
                console.log('Debug cleared');
            };

            window.testZXing = () => {
                if (window.ZXing) {
                    if (zxingStatusEl) {
                        zxingStatusEl.textContent = 'LOADED';
                        zxingStatusEl.className = 'font-mono text-green-600';
                    }
                    console.log('ZXing Library loaded successfully');
                    console.log('Available formats:', Object.keys(window.ZXing.BarcodeFormat));
                } else {
                    if (zxingStatusEl) {
                        zxingStatusEl.textContent = 'NOT LOADED';
                        zxingStatusEl.className = 'font-mono text-red-600';
                    }
                    console.error('ZXing Library not loaded');
                }
            };

            window.testBrowser = () => {
                const userAgent = navigator.userAgent;
                const isChrome = /Chrome/.test(userAgent) && /Google Inc/.test(navigator.vendor);
                const isAndroid = /Android/.test(userAgent);
                const isMobile = /Mobile|Android|iPhone|iPad/.test(userAgent);
                
                let browserInfo = '';
                if (isChrome && isAndroid) {
                    browserInfo = 'Chrome Android ✓';
                } else if (isChrome) {
                    browserInfo = 'Chrome Desktop';
                } else if (isAndroid) {
                    browserInfo = 'Other Android Browser';
                } else {
                    browserInfo = 'Unknown Browser';
                }
                
                if (browserInfoEl) {
                    browserInfoEl.textContent = browserInfo;
                    browserInfoEl.className = 'font-mono text-blue-600';
                }
                
                // Check HTTPS
                const isHttps = location.protocol === 'https:';
                if (httpsStatusEl) {
                    httpsStatusEl.textContent = isHttps ? 'HTTPS' : 'HTTP';
                    httpsStatusEl.className = isHttps ? 'font-mono text-green-600' : 'font-mono text-orange-600';
                }
                
                console.log('Browser Info:', {
                    userAgent,
                    isChrome,
                    isAndroid,
                    isMobile,
                    isHttps,
                    protocol: location.protocol
                });
            };

            window.testCamera = async () => {
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoDevices = devices.filter(device => device.kind === 'videoinput');
                    
                    if (cameraStatusEl) {
                        cameraStatusEl.textContent = `${videoDevices.length} camera(s)`;
                        cameraStatusEl.className = videoDevices.length > 0 ? 'font-mono text-green-600' : 'font-mono text-red-600';
                    }
                    
                    console.log(`Found ${videoDevices.length} camera(s):`);
                    videoDevices.forEach((device, index) => {
                        console.log(`${index + 1}. ${device.label || 'Unknown Camera'} (${device.deviceId})`);
                    });
                } catch (error) {
                    if (cameraStatusEl) {
                        cameraStatusEl.textContent = 'ERROR';
                        cameraStatusEl.className = 'font-mono text-red-600';
                    }
                    console.error('Camera enumeration failed:', error);
                }
            };

            // Auto test browser on load
            window.testBrowser();

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

                Livewire.on('restart-scanner', () => {
                    if (window.__zxingReader) {
                        window.__zxingReader.reset();
                    }
                    if (window.__scanTimeout) {
                        clearTimeout(window.__scanTimeout);
                    }
                    start();
                });
            });

            const start = async () => {
                if (!window.ZXing) {
                    setTimeout(() => start(), 200);
                    return;
                }

                // Update ZXing status
                if (zxingStatusEl) {
                    zxingStatusEl.textContent = 'LOADED';
                    zxingStatusEl.className = 'font-mono text-green-600';
                }

                setStatus('inisialisasi kamera…');

                const hints = new Map();
                hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.CODE_39,
                    ZXing.BarcodeFormat.EAN_13,
                    ZXing.BarcodeFormat.EAN_8,
                    ZXing.BarcodeFormat.UPC_A,
                    ZXing.BarcodeFormat.UPC_E,
                    ZXing.BarcodeFormat.QR_CODE,
                    ZXing.BarcodeFormat.DATA_MATRIX,
                    ZXing.BarcodeFormat.PDF_417,
                    ZXing.BarcodeFormat.AZTEC,
                ]);

                const codeReader = new ZXing.BrowserMultiFormatReader(hints);
                window.__zxingReader = codeReader;

                try {
                    const videoElement = document.getElementById('zxing-video');
                    setStatus('mendeteksi kamera…');
                    
                    // Coba dapatkan daftar kamera
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoDevices = devices.filter(device => device.kind === 'videoinput');
                    
                    // Update camera status
                    if (cameraStatusEl) {
                        cameraStatusEl.textContent = `${videoDevices.length} camera(s)`;
                        cameraStatusEl.className = videoDevices.length > 0 ? 'font-mono text-green-600' : 'font-mono text-red-600';
                    }
                    
                    if (videoDevices.length === 0) {
                        setStatus('tidak ada kamera ditemukan');
                        return;
                    }
                    
                    // Populate camera select
                    const cameraSelect = document.getElementById('camera-select');
                    if (cameraSelect) {
                        cameraSelect.innerHTML = '';
                        videoDevices.forEach((device, index) => {
                            const option = document.createElement('option');
                            option.value = device.deviceId;
                            option.text = device.label || `Kamera ${index + 1}`;
                            if (index === 0) option.selected = true;
                            cameraSelect.appendChild(option);
                        });
                    }
                    
                    // Prioritaskan kamera belakang untuk mobile
                    let deviceId = videoDevices[0].deviceId;
                    const backCamera = videoDevices.find(device => 
                        device.label.toLowerCase().includes('back') || 
                        device.label.toLowerCase().includes('environment') ||
                        device.label.toLowerCase().includes('kamera belakang')
                    );
                    if (backCamera) {
                        deviceId = backCamera.deviceId;
                    }
                    
                    setStatus('kamera aktif, menunggu barcode…');
                    
                    // Reset previous scan
                    if (window.__scanTimeout) {
                        clearTimeout(window.__scanTimeout);
                    }
                    
                    console.log('Starting scanner with device:', deviceId);
                    
                    // Continuous scanning untuk better detection
                    codeReader.decodeFromVideoDevice(
                        deviceId,
                        videoElement,
                        (result, error) => {
                            if (result) {
                                const decodedText = result.getText();
                                
                                // Debounce untuk避免 double scan
                                const now = Date.now();
                                if (decodedText === lastDecodedText && (now - lastDecodedAt) < 2000) {
                                    return;
                                }
                                
                                lastDecodedText = decodedText;
                                lastDecodedAt = now;
                                
                                console.log('Scan successful:', decodedText);
                                
                                if (lastEl) {
                                    lastEl.textContent = `Terbaca: ${decodedText}`;
                                }

                                if (debugResultEl) {
                                    debugResultEl.textContent = decodedText;
                                    debugResultEl.className = 'font-mono text-green-600';
                                }

                                // Play sound
                                playSound(successAudio);
                                
                                // Hide video temporarily
                                videoElement.style.display = 'none';
                                setStatus('scan berhasil, memproses…');
                                
                                // Send to backend
                                $wire.scanBarcode(decodedText);
                                
                                // Auto restart after 2.5 seconds
                                window.__scanTimeout = setTimeout(() => {
                                    videoElement.style.display = 'block';
                                    setStatus('kamera aktif, menunggu barcode…');
                                    lastDecodedText = null;
                                    lastDecodedAt = 0;
                                }, 2500);
                            }
                            
                            if (error && !(error instanceof ZXing.NotFoundException)) {
                                console.error('Scan error:', error);
                            }
                        }
                    );
                    
                } catch (e) {
                    console.error('Scanner initialization error:', e);
                    if (e.name === 'NotAllowedError') {
                        setStatus('kamera ditolak. Allow kamera di browser.');
                    } else if (e.name === 'NotFoundError') {
                        setStatus('kamera tidak ditemukan. Coba ganti browser.');
                    } else {
                        setStatus('error kamera: ' + e.message);
                    }
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

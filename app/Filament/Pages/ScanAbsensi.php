<?php

namespace App\Filament\Pages;

use App\Services\Absensi\AbsensiScanService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class ScanAbsensi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.scan-absensi';

    protected static ?string $navigationGroup = 'Master Absensi';

    protected static ?string $navigationLabel = 'Scan Absensi';

    protected static ?int $navigationSort = 1;

    public string $mode = 'auto';

    public string $manualBarcode = '';

    public ?array $lastScan = null;

    public function scanBarcode(string $barcode): void
    {
        try {
            $absensi = app(AbsensiScanService::class)->scan($barcode, $this->mode);

            $this->lastScan = [
                'tanggal' => $absensi->tanggal?->toDateString(),
                'jam_datang' => $absensi->jam_datang,
                'jam_pulang' => $absensi->jam_pulang,
                'status' => $absensi->status,
                'keterangan' => $absensi->keterangan,
                'siswa' => [
                    'nis' => $absensi->siswa?->nis,
                    'nama' => $absensi->siswa?->nama,
                    'kelas' => $absensi->siswa?->kelas?->nama_kelas,
                ],
            ];

            Notification::make()
                ->title('Scan berhasil')
                ->body("{$this->lastScan['siswa']['nama']} ({$this->lastScan['siswa']['kelas']}) - status: {$this->lastScan['status']}")
                ->success()
                ->send();

            $this->dispatch('scan-feedback', type: 'success');
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Scan gagal')
                ->body(collect($e->errors())->flatten()->first() ?? 'Terjadi kesalahan.')
                ->danger()
                ->send();

            $this->dispatch('scan-feedback', type: 'error');
        }
    }

    public function restartScanner(): void
    {
        // Method untuk restart scanner saat ganti kamera
        $this->dispatch('restart-scanner');
    }

    public function submitManualScan(): void
    {
        $barcode = trim($this->manualBarcode);
        if ($barcode === '') {
            Notification::make()
                ->title('Barcode kosong')
                ->danger()
                ->send();
            $this->dispatch('scan-feedback', type: 'error');
            return;
        }

        $this->scanBarcode($barcode);
    }
}

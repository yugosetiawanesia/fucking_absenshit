<?php

namespace App\Services\Absensi;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Siswa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsensiScanService
{
    public function scan(string $barcode, string $mode = 'auto', ?CarbonImmutable $now = null): Absensi
    {
        $now ??= CarbonImmutable::now();
        $tanggal = $now->toDateString();

        $siswa = Siswa::query()->where('barcode', $barcode)->first();

        if (! $siswa) {
            throw ValidationException::withMessages([
                'barcode' => 'Barcode tidak ditemukan.',
            ]);
        }

        // Minggu otomatis libur
        if ($now->isSunday()) {
            return $this->upsertLibur($siswa->id, $tanggal, 'Hari Minggu');
        }

        $libur = HariLibur::query()->whereDate('tanggal', $tanggal)->first();
        if ($libur) {
            return $this->upsertLibur($siswa->id, $tanggal, $libur->keterangan);
        }

        return DB::transaction(function () use ($siswa, $tanggal, $now, $mode) {
            /** @var Absensi $absensi */
            $absensi = Absensi::query()->firstOrCreate(
                ['siswa_id' => $siswa->id, 'tanggal' => $tanggal],
                ['status' => 'hadir'],
            );

            // Jika sebelumnya libur dan di-scan (harusnya tidak terjadi), ubah ke hadir
            if ($absensi->status === 'libur') {
                $absensi->status = 'hadir';
            }

            if ($mode === 'masuk') {
                if ($absensi->jam_datang) {
                    throw ValidationException::withMessages([
                        'barcode' => 'Jam masuk hari ini sudah tercatat.',
                    ]);
                }

                $absensi->jam_datang = $now->format('H:i:s');
                $absensi->status = 'hadir';
                $absensi->save();

                return $absensi;
            }

            if ($mode === 'pulang') {
                if (! $absensi->jam_datang) {
                    throw ValidationException::withMessages([
                        'barcode' => 'Belum tercatat jam masuk. Silakan scan masuk terlebih dahulu.',
                    ]);
                }

                if ($absensi->jam_pulang) {
                    throw ValidationException::withMessages([
                        'barcode' => 'Jam pulang hari ini sudah tercatat.',
                    ]);
                }

                $absensi->jam_pulang = $now->format('H:i:s');
                $absensi->status = 'hadir';
                $absensi->save();

                return $absensi;
            }

            // Auto: scan pertama -> masuk, scan kedua -> pulang
            if (! $absensi->jam_datang) {
                $absensi->jam_datang = $now->format('H:i:s');
                $absensi->status = 'hadir';
                $absensi->save();

                return $absensi;
            }

            if (! $absensi->jam_pulang) {
                $absensi->jam_pulang = $now->format('H:i:s');
                $absensi->status = 'hadir';
                $absensi->save();

                return $absensi;
            }

            throw ValidationException::withMessages([
                'barcode' => 'Absensi hari ini sudah lengkap (datang & pulang).',
            ]);
        });
    }

    private function upsertLibur(int $siswaId, string $tanggal, string $keterangan): Absensi
    {
        /** @var Absensi $absensi */
        $absensi = Absensi::query()->updateOrCreate(
            ['siswa_id' => $siswaId, 'tanggal' => $tanggal],
            [
                'status' => 'libur',
                'keterangan' => $keterangan,
                'jam_datang' => null,
                'jam_pulang' => null,
            ],
        );

        return $absensi;
    }
}

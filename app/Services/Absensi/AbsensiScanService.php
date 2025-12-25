<?php

namespace App\Services\Absensi;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\LiburSemester;
use App\Models\Setting;
use App\Models\Siswa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $allowSundayAttendance = false;
        $workdaysPerWeek = 6;
        $autoScanCooldownSeconds = 60;
        if (Schema::hasTable('settings')) {
            $allowSundayAttendance = Setting::getBool('absensi.allow_sunday_attendance', false);
            $workdaysPerWeek = (int) (Setting::getString('absensi.workdays_per_week', '6') ?? '6');

            $cooldownMinutes = Setting::getString('absensi.auto_scan_cooldown_minutes', null);
            if ($cooldownMinutes !== null) {
                $autoScanCooldownSeconds = max(0, (int) $cooldownMinutes) * 60;
            } else {
                $autoScanCooldownSeconds = (int) (Setting::getString('absensi.auto_scan_cooldown_seconds', '60') ?? '60');
            }
        }

        if ($now->isSunday() && ! $allowSundayAttendance) {
            return $this->upsertLibur($siswa->id, $tanggal, 'Hari Minggu');
        }

        if ($now->isSaturday() && $workdaysPerWeek === 5) {
            return $this->upsertLibur($siswa->id, $tanggal, 'Hari Sabtu');
        }

        $libur = HariLibur::query()->whereDate('tanggal', $tanggal)->first();
        if ($libur) {
            return $this->upsertLibur($siswa->id, $tanggal, $libur->keterangan);
        }

        // Cek libur semester
        $liburSemester = LiburSemester::query()
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->first();
        if ($liburSemester) {
            return $this->upsertLibur($siswa->id, $tanggal, $liburSemester->nama_libur);
        }

        return DB::transaction(function () use ($siswa, $tanggal, $now, $mode, $autoScanCooldownSeconds) {
            /** @var Absensi $absensi */
            $absensi = Absensi::query()->firstOrCreate(
                ['siswa_id' => $siswa->id, 'tanggal' => $tanggal],
                ['status' => 'hadir'],
            );

            $assertCooldownPassed = function () use ($absensi, $tanggal, $now, $autoScanCooldownSeconds): void {
                if ($autoScanCooldownSeconds <= 0 || ! $absensi->jam_datang) {
                    return;
                }

                $timezone = config('app.timezone', 'UTC');
                $jamDatang = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $tanggal.' '.$absensi->jam_datang, $timezone);
                $elapsed = $jamDatang->diffInSeconds($now, false);
                if ($elapsed < 0) {
                    $elapsed = 0;
                }

                if ($elapsed < $autoScanCooldownSeconds) {
                    $remaining = $autoScanCooldownSeconds - $elapsed;

                    $remainingLabel = $remaining.' detik';
                    if ($remaining >= 60) {
                        $minutes = (int) floor($remaining / 60);
                        $seconds = $remaining % 60;
                        $remainingLabel = $minutes.' menit'.($seconds > 0 ? ' '.$seconds.' detik' : '');
                    }

                    throw ValidationException::withMessages([
                        'barcode' => "Scan terlalu cepat. Tunggu {$remainingLabel} lagi.",
                    ]);
                }
            };

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

                $assertCooldownPassed();

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
                $assertCooldownPassed();

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

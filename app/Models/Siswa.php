<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nama',
        'jenis_kelamin',
        'kelas_id',
        'barcode',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'siswa_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $siswa) {
            if (! filled($siswa->barcode) && filled($siswa->nis)) {
                $siswa->barcode = 'SMPN1PIANI-' . Str::upper(trim($siswa->nis));
            }
        });
    }
}

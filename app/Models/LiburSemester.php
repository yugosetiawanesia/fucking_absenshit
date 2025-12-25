<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiburSemester extends Model
{
    protected $table = 'libur_semesters';

    protected $fillable = [
        'nama_libur',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'semester_id',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}

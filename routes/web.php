<?php

use App\Http\Controllers\SiswaCardController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/siswa/{siswa}/kartu', [SiswaCardController::class, 'show'])->name('siswa.kartu');
    Route::get('/print/laporan/bulanan', [PrintController::class, 'bulanan'])->name('print.laporan.bulanan');
    Route::get('/print/laporan/harian', [PrintController::class, 'harian'])->name('print.laporan.harian');
});

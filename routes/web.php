<?php

use App\Http\Controllers\SiswaCardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/siswa/{siswa}/kartu', [SiswaCardController::class, 'show'])->name('siswa.kartu');
});

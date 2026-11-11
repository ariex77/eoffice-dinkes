<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\PenomoranController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\SuratMasukController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    // Surat masuk
    Route::get('surat-masuk/{suratMasuk}/berkas', [SuratMasukController::class, 'berkas'])->name('surat-masuk.berkas');
    Route::resource('surat-masuk', SuratMasukController::class)
        ->parameters(['surat-masuk' => 'suratMasuk'])->except(['destroy']);

    // Surat keluar
    Route::get('surat-keluar/{suratKeluar}/berkas', [SuratKeluarController::class, 'berkas'])->name('surat-keluar.berkas');
    Route::post('surat-keluar/{suratKeluar}/registrasi', [SuratKeluarController::class, 'registrasi'])->name('surat-keluar.registrasi');
    Route::resource('surat-keluar', SuratKeluarController::class)
        ->parameters(['surat-keluar' => 'suratKeluar']);

    // Penomoran
    Route::get('penomoran', [PenomoranController::class, 'index'])->name('penomoran.index');
    Route::get('penomoran/ambil', [PenomoranController::class, 'create'])->name('penomoran.create');
    Route::post('penomoran', [PenomoranController::class, 'store'])->name('penomoran.store');
    Route::post('penomoran/{nomorSurat}/batal', [PenomoranController::class, 'batal'])->name('penomoran.batal');

    // Disposisi
    Route::get('disposisi', [DisposisiController::class, 'index'])->name('disposisi.index');
    Route::post('surat-masuk/{suratMasuk}/disposisi', [DisposisiController::class, 'store'])->name('disposisi.store');
    Route::post('disposisi/{disposisi}/proses', [DisposisiController::class, 'proses'])->name('disposisi.proses');
    Route::post('disposisi/{disposisi}/selesai', [DisposisiController::class, 'selesai'])->name('disposisi.selesai');

    // Pengguna (admin)
    Route::resource('users', UserController::class)
        ->except(['show', 'destroy'])
        ->middleware(RoleMiddleware::class.':admin');
});

<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Berita (Posts) — listing + detail. Didefinisikan sebelum catch-all halaman.
Route::get('/berita', [PostController::class, 'index'])->name('posts.index');
Route::get('/berita/{post:slug}', [PostController::class, 'show'])->name('posts.show');

// Galeri (foto & video). Didefinisikan sebelum catch-all halaman.
Route::get('/galeri', [GalleryController::class, 'index'])->name('galeri.index');

// Form publik (form builder). Didefinisikan sebelum catch-all halaman.
Route::get('/form/{form:slug}', [FormController::class, 'show'])->name('forms.show');
Route::post('/form/{form:slug}', [FormController::class, 'submit'])->name('forms.submit');

// Halaman statis by slug (catch-all satu segmen) — WAJIB paling akhir.
// Regex mengecualikan segmen "admin", "up", & "form" agar tidak pernah menutupi panel
// Filament / health check / form routes, apa pun urutan registrasi rutenya.
Route::get('/{page:slug}', [PageController::class, 'show'])
    ->where('page', '(?!admin$|up$|form$)[A-Za-z0-9._-]+')
    ->name('pages.show');

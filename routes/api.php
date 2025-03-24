<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\BookController;
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//authControlller
Route::post('/register', [authController::class, 'register']);
Route::post('/login', [authController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function() {
Route::get('/user',[authController::class, 'user']);
Route::get('/siswa',[authController::class, 'siswa']);
Route::get('/siswa-sd',[authController::class, 'sdSiswa']);
Route::get('/siswa-smp',[authController::class, 'smpSiswa']);
Route::get('/siswa-smk',[authController::class, 'smkSiswa']);
Route::get('/guru',[authController::class, 'guru']);
Route::get('/perpus',[authController::class, 'perpus']);
Route::post('/logout', [authController::class, 'logout']);
Route::post('/change-password', [authController::class, 'changePassword']);
});

Route::get('/siswa/{id}', [authController::class, 'getSiswa']);
Route::get('/siswa-sd/{id}', [authController::class, 'getSdSiswa']);
Route::get('/siswa-smp/{id}', [authController::class, 'getSmpSiswa']);
Route::get('/siswa-smk/{id}', [authController::class, 'getSmkSiswa']);
Route::get('/guru/{id}', [authController::class, 'getGuru']);
Route::get('/perpus/{id}', [authController::class, 'getPerpus']);
Route::delete('/delete/{id}', [authController::class, 'deleteUser']);
Route::delete('/siswa/{id}', [authController::class, 'deleteSiswa']);
Route::delete('/siswa-sd/{id}', [authController::class, 'deleteSdSiswa']);
Route::delete('/siswa-smp/{id}', [authController::class, 'deleteSmpSiswa']);
Route::delete('/siswa-smk/{id}', [authController::class, 'deleteSmkSiswa']);
Route::delete('/guru/{id}', [authController::class, 'deleteGuru']);
Route::delete('/perpus/{id}', [authController::class, 'deletePerpus']);
Route::post('/update/{id}', [authController::class, 'updateUser']);
Route::post('/update-siswa/{id}',[authController::class, 'updateSiswa']);
Route::post('/update-siswa-sd/{id}', [authController::class, 'updateSdSiswa']);
Route::post('/update-siswa-smp/{id}', [authController::class, 'updateSmpSiswa']);
Route::post('update-siswa-smk/{id}', [authController::class, 'updateSmkSiswa']);
Route::post('/update-guru/{id}',[authController::class, 'updateGuru']);
Route::post('/update-perpus/{id}',[authController::class, 'updatePerpus']);



//BookController
Route::post('/books', [BookController::class, 'store']); // Tambah buku
Route::get('/books', [BookController::class, 'index']); // Ambil semua buku
Route::get('/books/{id}', [BookController::class, 'show']); // Ambil buku berdasarkan ID
Route::post('/books/{id}', [BookController::class, 'update']); // Update buku berdasarkan ID
Route::delete('/books/{id}', [BookController::class, 'destroy']); // Hapus buku berdasarkan ID


Route::get('/books-siswa', [BookController::class, 'getSiswaBooks']);
Route::get('/books-guru', [BookController::class, 'getGuruBooks']);
Route::get('/books-perpus', [BookController::class, 'getPerpusBooks']);
Route::get('/books-non-akademik', [BookController::class, 'getNonAkademikBooks']);
// 📌 GET Buku Berdasarkan ID
Route::get('/books/siswa/{id}', [BookController::class, 'getSiswaBookById']);
Route::get('/books/guru/{id}', [BookController::class, 'getGuruBookById']);
Route::get('/books-perpus/{id}', [BookController::class, 'getPerpusBookById']);
Route::get('/books/non_akademik/{id}', [BookController::class, 'getNonAkademikBookById']);
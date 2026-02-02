<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\KunjunganController;



//authControlller
Route::get('/debug-users', function() {
    return App\Models\User::all(['id', 'username', 'kode', 'role', 'is_approved']);
});
Route::post('/register', [authController::class, 'register']);
Route::post('/register2', [authController::class, 'register2']);
Route::post('/login', [authController::class, 'login'])->name('login');
Route::get('/pdf/{filename}', [PdfController::class, 'serve'])->where('filename', '.*');


Route::middleware('auth:sanctum')->group(function() {
Route::get('/user',[authController::class, 'user']);
Route::get('/siswa',[authController::class, 'siswa']);
Route::get('/siswa-sd',[authController::class, 'sdSiswa']);
Route::get('/siswa-smp',[authController::class, 'smpSiswa']);
Route::get('/siswa-smk',[authController::class, 'smkSiswa']);
Route::get('/guru',[authController::class, 'guru']);
Route::get('/guru-sd',[authController::class, 'sdGuru']);
Route::get('/guru-smp',[authController::class, 'smpGuru']);
Route::get('/guru-smk',[authController::class, 'smkGuru']);
Route::get('/perpus',[authController::class, 'perpus']);
Route::post('/logout', [authController::class, 'logout']);
Route::post('/change-password', [authController::class, 'changePassword']);

Route::get('/kunjungans', [KunjunganController::class, 'indexHariIni']);
Route::get('/data-kunjungans', [KunjunganController::class, 'index']);
Route::get('/rekap-kunjungan-books', [KunjunganController::class, 'index']); // Fix for 404 error
Route::get('/kunjungans/rekap', [KunjunganController::class, 'rekap']);
Route::get('/kunjungans/rekap2', [KunjunganController::class, 'rekap2']);

  

Route::get('/users-pending', [authController::class, 'pendingUsers']);
Route::post('/approve-user/{id}', [authController::class, 'approveUser']);
Route::delete('/reject-user/{id}', [authController::class, 'rejectUser']);

Route::get('/siswa/{id}', [authController::class, 'getSiswa']);
Route::get('/siswa-sd/{id}', [authController::class, 'getSdSiswa']);
Route::get('/siswa-smp/{id}', [authController::class, 'getSmpSiswa']);
Route::get('/siswa-smk/{id}', [authController::class, 'getSmkSiswa']);
Route::get('/guru/{id}', [authController::class, 'getGuru']);
Route::get('/guru-sd/{id}', [authController::class, 'getSdGuru']);
Route::get('/guru-smp/{id}', [authController::class, 'getSmpGuru']);
Route::get('/guru-smk/{id}', [authController::class, 'getSmkGuru']);
Route::get('/perpus/{id}', [authController::class, 'getPerpus']);
Route::delete('/delete/{id}', [authController::class, 'deleteUser']);
Route::delete('/siswa/{id}', [authController::class, 'deleteSiswa']);
Route::delete('/siswa-sd/{id}', [authController::class, 'deleteSdSiswa']);
Route::delete('/siswa-smp/{id}', [authController::class, 'deleteSmpSiswa']);
Route::delete('/siswa-smk/{id}', [authController::class, 'deleteSmkSiswa']);
Route::delete('/guru/{id}', [authController::class, 'deleteGuru']);
Route::delete('/guru-sd/{id}', [authController::class, 'deleteSdGuru']);
Route::delete('/guru-smp/{id}', [authController::class, 'deleteSmpGuru']);
Route::delete('/guru-smk/{id}', [authController::class, 'deleteSmkGuru']);
Route::delete('/perpus/{id}', [authController::class, 'deletePerpus']);
Route::post('/update/{id}', [authController::class, 'updateUser']);
Route::post('/update-siswa/{id}',[authController::class, 'updateSiswa']);
Route::post('/update-siswa-sd/{id}', [authController::class, 'updateSdSiswa']);
Route::post('/update-siswa-smp/{id}', [authController::class, 'updateSmpSiswa']);
Route::post('update-siswa-smk/{id}', [authController::class, 'updateSmkSiswa']);
Route::post('/update-guru/{id}',[authController::class, 'updateGuru']);
Route::post('/update-guru-sd/{id}', [authController::class, 'updateSdGuru']);
Route::post('/update-guru-smp/{id}', [authController::class, 'updateSmpGuru']);
Route::post('/update-guru-smk/{id}', [authController::class, 'updateSmkGuru']);
Route::post('/update-perpus/{id}',[authController::class, 'updatePerpus']);



//BookController
Route::post('/books', [BookController::class, 'store']); // Tambah buku
Route::get('/books', [BookController::class, 'index']); // Ambil semua buku
Route::get('/books/{id}', [BookController::class, 'show']); // Ambil buku berdasarkan ID
Route::put('/books/{id}', [BookController::class, 'update']); // Update buku berdasarkan ID
Route::delete('/books/{id}', [BookController::class, 'destroy']); // Hapus buku berdasarkan ID

Route::get('/books/{id}/isi', [BookController::class, 'getIsiPdf']);

  
Route::get('/books-kelas-1', [BookController::class, 'getKelas1Books']);
Route::get('/books-kelas-2', [BookController::class, 'getKelas2Books']);
Route::get('/books-kelas-3', [BookController::class, 'getKelas3Books']);
Route::get('/books-kelas-4', [BookController::class, 'getKelas4Books']);
Route::get('/books-kelas-5', [BookController::class, 'getKelas5Books']);
Route::get('/books-kelas-6', [BookController::class, 'getKelas6Books']);   
Route::get('/books-kelas-7', [BookController::class, 'getKelas7Books']);
Route::get('/books-kelas-8', [BookController::class, 'getKelas8Books']);
Route::get('/books-kelas-9', [BookController::class, 'getKelas9Books']);
Route::get('/books-kelas-10', [BookController::class, 'getKelas10Books']);
Route::get('/books-kelas-11', [BookController::class, 'getKelas11Books']);
Route::get('/books-kelas-12', [BookController::class, 'getKelas12Books']);
Route::get('/books-non-akademik', [BookController::class, 'getNonAkademikBooks']);

Route::get('/kunjungan-books', [KunjunganController::class, 'indexKunjunganBook']);
Route::get('/kunjungan-books/hari-ini', [KunjunganController::class, 'kunjunganBookHariIni']);
Route::get('/rekap-kunjungan-books', [KunjunganController::class, 'rekapKunjunganBook']); 

Route::get('/books-siswa', [BookController::class, 'getSiswaBooks']); 
Route::get('/books-guru', [BookController::class, 'getGuruBooks']);
Route::delete('/books-guru/{id}', [BookController::class, 'deleteGuruBookById']);
Route::get('/books-perpus', [BookController::class, 'getPerpusBooks']);

Route::put('/books-kelas-1/{id}', [BookController::class, 'updateKelas1Book']);
Route::put('/books-kelas-2/{id}', [BookController::class, 'updateKelas2Book']);
Route::put('/books-kelas-3/{id}', [BookController::class, 'updateKelas3Book']);
Route::put('/books-kelas-4/{id}', [BookController::class, 'updateKelas4Book']);
Route::put('/books-kelas-5/{id}', [BookController::class, 'updateKelas5Book']);
Route::put('/books-kelas-6/{id}', [BookController::class, 'updateKelas6Book']);
Route::put('/books-kelas-7/{id}', [BookController::class, 'updateKelas7Book']);
Route::put('/books-kelas-8/{id}', [BookController::class, 'updateKelas8Book']);
Route::put('/books-kelas-9/{id}', [BookController::class, 'updateKelas9Book']);
Route::put('/books-kelas-10/{id}', [BookController::class, 'updateKelas10Book']);
Route::put('/books-kelas-11/{id}', [BookController::class, 'updateKelas11Book']);
Route::put('/books-kelas-12/{id}', [BookController::class, 'updateKelas12Book']);
Route::put('/books-non-akademik/{id}', [BookController::class, 'updateNonAkademikBook']);


// 📌 GET Buku Berdasarkan ID
Route::get('/books/siswa/{id}', [BookController::class, 'getSiswaBookById']);
Route::get('/books-kelas-1/{id}', [BookController::class, 'getKelas1BookById']);
Route::get('/books-kelas-2/{id}', [BookController::class, 'getKelas2BookById']);
Route::get('/books-kelas-3/{id}', [BookController::class, 'getKelas3BookById']);
Route::get('/books-kelas-4/{id}', [BookController::class, 'getKelas4BookById']);
Route::get('/books-kelas-5/{id}', [BookController::class, 'getKelas5BookById']);
Route::get('/books-kelas-6/{id}', [BookController::class, 'getKelas6BookById']);
Route::get('/books-kelas-7/{id}', [BookController::class, 'getKelas7BookById']);
Route::get('/books-kelas-8/{id}', [BookController::class, 'getKelas8BookById']);
Route::get('/books-kelas-9/{id}', [BookController::class, 'getKelas9BookById']);
Route::get('/books-kelas-10/{id}', [BookController::class, 'getKelas10BookById']);
Route::get('/books-kelas-11/{id}', [BookController::class, 'getKelas11BookById']);
Route::get('/books-kelas-12/{id}', [BookController::class, 'getKelas12BookById']);
Route::get('/books/guru/{id}', [BookController::class, 'getGuruBookById']);
Route::get('/books-perpus/{id}', [BookController::class, 'getPerpusBookById']);
Route::get('/books/non-akademik/{id}', [BookController::class, 'getNonAkademikBookById']);

Route::delete('/books-kelas-1/{id}', [BookController::class, 'deleteKelas1Book']);
Route::delete('/books-kelas-2/{id}', [BookController::class, 'deleteKelas2Book']);
Route::delete('/books-kelas-3/{id}', [BookController::class, 'deleteKelas3Book']);
Route::delete('/books-kelas-4/{id}', [BookController::class, 'deleteKelas4Book']);
Route::delete('/books-kelas-5/{id}', [BookController::class, 'deleteKelas5Book']);
Route::delete('/books-kelas-6/{id}', [BookController::class, 'deleteKelas6Book']);
Route::delete('/books-kelas-7/{id}', [BookController::class, 'deleteKelas7Book']);
Route::delete('/books-kelas-8/{id}', [BookController::class, 'deleteKelas8Book']);
Route::delete('/books-kelas-9/{id}', [BookController::class, 'deleteKelas9Book']);
Route::delete('/books-kelas-10/{id}', [BookController::class, 'deleteKelas10Book']);
Route::delete('/books-kelas-11/{id}', [BookController::class, 'deleteKelas11Book']);
Route::delete('/books-kelas-12/{id}', [BookController::class, 'deleteKelas12Book']);
Route::delete('/books-non-akademik/{id}', [BookController::class, 'deleteNonAkademikBook']);
});


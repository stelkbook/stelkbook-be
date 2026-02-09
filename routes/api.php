<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\KunjunganController;



//authControlller
Route::get('/debug-users', function() {
    return App\Models\User::all(['id', 'username', 'kode', 'role', 'is_approved']);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register2', [AuthController::class, 'register2']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/pdf/{filename}', [PdfController::class, 'serve'])->where('filename', '.*');

// Public Book Routes
Route::get('/books', [BookController::class, 'index']); // Ambil semua buku
Route::get('/books/{id}', [BookController::class, 'show']); // Ambil buku berdasarkan ID
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
Route::get('/books/non-akademik/{id}', [BookController::class, 'getNonAkademikBookById']);

Route::middleware('auth:sanctum')->group(function() {
Route::get('/user',[AuthController::class, 'user']);
Route::get('/siswa',[AuthController::class, 'siswa']);
Route::get('/siswa-sd',[AuthController::class, 'sdSiswa']);
Route::get('/siswa-smp',[AuthController::class, 'smpSiswa']);
Route::get('/siswa-smk',[AuthController::class, 'smkSiswa']);
Route::get('/guru',[AuthController::class, 'guru']);
Route::get('/guru-sd',[AuthController::class, 'sdGuru']);
Route::get('/guru-smp',[AuthController::class, 'smpGuru']);
Route::get('/guru-smk',[AuthController::class, 'smkGuru']);
Route::get('/perpus',[AuthController::class, 'perpus']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/change-password', [AuthController::class, 'changePassword']);

Route::get('/kunjungans', [KunjunganController::class, 'indexHariIni']);
Route::get('/data-kunjungans', [KunjunganController::class, 'index']);
Route::get('/rekap-kunjungan-books', [KunjunganController::class, 'index']); // Fix for 404 error
Route::get('/kunjungans/rekap', [KunjunganController::class, 'rekap']);
Route::get('/kunjungans/rekap2', [KunjunganController::class, 'rekap2']);

  

Route::get('/users-pending', [AuthController::class, 'pendingUsers']);
Route::post('/approve-user/{id}', [AuthController::class, 'approveUser']);
Route::delete('/reject-user/{id}', [AuthController::class, 'rejectUser']);

Route::get('/siswa/{id}', [AuthController::class, 'getSiswa']);
Route::get('/siswa-sd/{id}', [AuthController::class, 'getSdSiswa']);
Route::get('/siswa-smp/{id}', [AuthController::class, 'getSmpSiswa']);
Route::get('/siswa-smk/{id}', [AuthController::class, 'getSmkSiswa']);
Route::get('/guru/{id}', [AuthController::class, 'getGuru']);
Route::get('/guru-sd/{id}', [AuthController::class, 'getSdGuru']);
Route::get('/guru-smp/{id}', [AuthController::class, 'getSmpGuru']);
Route::get('/guru-smk/{id}', [AuthController::class, 'getSmkGuru']);
Route::get('/perpus/{id}', [AuthController::class, 'getPerpus']);
Route::delete('/delete/{id}', [AuthController::class, 'deleteUser']);
Route::delete('/siswa/{id}', [AuthController::class, 'deleteSiswa']);
Route::delete('/siswa-sd/{id}', [AuthController::class, 'deleteSdSiswa']);
Route::delete('/siswa-smp/{id}', [AuthController::class, 'deleteSmpSiswa']);
Route::delete('/siswa-smk/{id}', [AuthController::class, 'deleteSmkSiswa']);
Route::delete('/guru/{id}', [AuthController::class, 'deleteGuru']);
Route::delete('/guru-sd/{id}', [AuthController::class, 'deleteSdGuru']);
Route::delete('/guru-smp/{id}', [AuthController::class, 'deleteSmpGuru']);
Route::delete('/guru-smk/{id}', [AuthController::class, 'deleteSmkGuru']);
Route::delete('/perpus/{id}', [AuthController::class, 'deletePerpus']);
Route::post('/update/{id}', [AuthController::class, 'updateUser']);
Route::post('/update-siswa/{id}',[AuthController::class, 'updateSiswa']);
Route::post('/update-siswa-sd/{id}', [AuthController::class, 'updateSdSiswa']);
Route::post('/update-siswa-smp/{id}', [AuthController::class, 'updateSmpSiswa']);
Route::post('update-siswa-smk/{id}', [AuthController::class, 'updateSmkSiswa']);
Route::post('/update-guru/{id}',[AuthController::class, 'updateGuru']);
Route::post('/update-guru-sd/{id}', [AuthController::class, 'updateSdGuru']);
Route::post('/update-guru-smp/{id}', [AuthController::class, 'updateSmpGuru']);
Route::post('/update-guru-smk/{id}', [AuthController::class, 'updateSmkGuru']);
Route::post('/update-perpus/{id}',[AuthController::class, 'updatePerpus']);



//BookController
Route::post('/books', [BookController::class, 'store']); // Tambah buku
Route::put('/books/{id}', [BookController::class, 'update']); // Update buku berdasarkan ID
Route::delete('/books/{id}', [BookController::class, 'destroy']); // Hapus buku berdasarkan ID

  

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
Route::get('/books/guru/{id}', [BookController::class, 'getGuruBookById']);
Route::get('/books-perpus/{id}', [BookController::class, 'getPerpusBookById']);

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

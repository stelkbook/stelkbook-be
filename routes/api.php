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
Route::get('/siswa', [AuthController::class, 'getSiswa']);
Route::get('/guru', [AuthController::class, 'getGuru']);
Route::get('/perpus', [AuthController::class, 'getPerpus']);
Route::post('/logout', [authController::class, 'logout']);
Route::post('/change-password', [authController::class, 'changePassword']);
Route::delete('/delete-user', [authController::class, 'deleteUser']);
Route::post('/update-user', [authController::class, 'updateuser']);
});


//BookController
Route::post('/books',[BookController::class, 'store']);
Route::get('/books/{book}',[BookController::class, 'show']);
Route::put('/books/{book}', [BookController::class, 'update']);
Route::delete('/books/{book}',[BookController::class, 'destroy']);
Route::get('/books', [BookController::class, 'index']); 
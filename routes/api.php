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
    Route::post('/logout', [authController::class, 'logout']);
});

//BookController
Route::post('/books',[BookController::class, 'store']);
Route::get('/books/{book}',[BookController::class, 'show']);
Route::delete('/books/{book}',[BookController::class, 'destroy']);
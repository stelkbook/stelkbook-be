<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('kode')->unique(); // Menggunakan string agar lebih fleksibel
            $table->enum('role', ['Siswa', 'Guru', 'Admin', 'Perpus']);
            $table->enum('gender', ['Laki-Laki', 'Perempuan']);
            $table->enum('sekolah', ['SD', 'SMP', 'SMK'])->nullable(); // Hanya untuk Siswa & Guru
            $table->enum('kelas', [
                'I', 'II', 'III', 'IV', 'V', 'VI', // SD
                'VII', 'VIII', 'IX', // SMP
                'X', 'XI', 'XII' // SMK
            ])->nullable(); // Hanya berlaku untuk Siswa
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

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
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('username');
            $table->string('email');
            $table->string('kode'); // Menggunakan string agar lebih fleksibel
            $table->enum('role', ['Siswa', 'Guru', 'Admin', 'Perpus']);
            $table->enum('gender', ['Laki-Laki', 'Perempuan']);
            $table->enum('sekolah', ['SD', 'SMP', 'SMK'])->nullable(); // Hanya untuk Siswa & Guru
            $table->enum('kelas', [
                'I', 'II', 'III', 'IV', 'V', 'VI', // SD
                'VII', 'VIII', 'IX', // SMP
                'X', 'XI', 'XII' // SMK
            ])->nullable(); // Hanya berlaku untuk Siswa
            $table->string('avatar')->nullable();
             $table->timestamp('tanggal_kunjungan')->useCurrent(); // default waktu sekarang
             $table->date('tanggal_kunjungan_hari');
             $table->string('tanggal_kunjungan_bulan');
             $table->string('tanggal_kunjungan_tahun');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};

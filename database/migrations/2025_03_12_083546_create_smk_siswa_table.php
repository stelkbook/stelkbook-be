<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smk_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('nis')->unique();
            $table->enum('sekolah',['SMK']);
            $table->enum('gender', ['Laki-Laki', 'Perempuan']);
            $table->enum('kelas', ['X', 'XI', 'XII']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smk_siswas');
    }
};
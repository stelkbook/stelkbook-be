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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->enum('kategori',['X','XI','XII','NA']);
            $table->string('penerbit');
            $table->string('penulis');
            $table->integer('ISBN');
            $table->string('isi')->nullable();
            $table->string('cover')->nullable();
            // $table->timestamp('email_verified_at')->nullable();
            // $table->string('password');
            // $table->string('kode')->unique();
            // $table->enum('gender',['laki-laki','perempuan']);
            // $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};

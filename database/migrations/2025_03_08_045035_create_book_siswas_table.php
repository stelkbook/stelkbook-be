<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookSiswasTable extends Migration
{
    public function up(): void
    {
        Schema::create('book_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->string('judul')->unique();
            $table->text('deskripsi')->nullable();
            $table->enum("sekolah", ['SD', 'SMP', 'SMK'])->nullable();
            $table->enum('kategori', [
                'I', 'II', 'III', 'IV', 'V', 'VI',
                'VII', 'VIII', 'IX',
                'X', 'XI', 'XII',
                'NA'
            ])->nullable();
            $table->string('penerbit');
            $table->string('penulis');
            $table->year('tahun');
            $table->integer('ISBN')->unique();
            $table->string('cover')->nullable();
            $table->string('isi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_siswas');
    }
}

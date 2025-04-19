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
        Schema::create('book_9_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->string('judul')->unique();
            $table->text('deskripsi')->nullable();
            $table->enum("sekolah",['SD','SMP','SMK'])->nullable();
            $table->enum('kategori',[
                'I','II','III','IV','V','VI',
                'VII','VIII','IX',
                'X','XI','XII',
                'NA']) -> nullable();
            $table->string('penerbit');
            $table->string('penulis');
            $table->year('tahun');
            $table->integer('ISBN')->unique();
            $table->string('cover')->nullable();
            $table->string('isi')->nullable();
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
        Schema::dropIfExists('book_9_classes');
    }
};

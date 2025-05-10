<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class KunjunganBook extends Model
{
    use HasFactory;

    protected $table = 'kunjungan_books';

    protected $fillable = [
        'book_id',
        'judul',
        'deskripsi',
        'sekolah',
        'kategori',
        'penerbit',
        'penulis',
        'tahun',
        'ISBN',
        'cover',
        'tanggal_kunjungan',
    ];

    /**
     * Relasi: KunjunganBook milik satu Book
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

      public function getCoverUrlAttribute()
    {
        return $this->cover ? Storage::url($this->cover) : null;
    }
}

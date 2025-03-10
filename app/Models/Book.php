<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'deskripsi',
        'sekolah',
        'kategori',
        'penerbit',
        'penulis',
        'tahun',
        'ISBN',
        'cover',
        'isi',
    ];

    // Tambahkan atribut tambahan untuk JSON response
    protected $appends = ['pdf_url', 'cover_url'];

    // Accessor untuk URL PDF
    public function getPdfUrlAttribute()
    {
        return $this->isi ? Storage::url($this->isi) : null;
    }

    // Accessor untuk URL Cover
    public function getCoverUrlAttribute()
    {
        return $this->cover ? Storage::url($this->cover) : null;
    }

    public function bookSiswas()
    {
        return $this->hasMany(BookSiswa::class, 'book_id');
    }
    
    public function bookGurus()
    {
        return $this->hasMany(BookGuru::class, 'book_id');
    }
    
    public function bookPerpuses()
    {
        return $this->hasMany(BookPerpus::class, 'book_id');
    }
    
    public function bookNonAkademiks()
    {
        return $this->hasMany(BookNonAkademik::class, 'book_id');
    }
}

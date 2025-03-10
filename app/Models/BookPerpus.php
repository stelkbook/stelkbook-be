<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BookPerpus extends Model
{
    use HasFactory;

    protected $table = 'book_perpuses';

    protected $fillable = [
        'book_id', 'judul', 'deskripsi', 'sekolah', 'kategori',
        'penerbit', 'penulis', 'tahun', 'ISBN', 'cover', 'isi'
    ];

    public function book()
{
    return $this->belongsTo(Book::class, 'book_id');
}

    public function getPdfUrlAttribute()
    {
        return $this->isi ? Storage::url($this->isi) : null;
    }

    // Accessor untuk URL Cover
    public function getCoverUrlAttribute()
    {
        return $this->cover ? Storage::url($this->cover) : null;
    }
}

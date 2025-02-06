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
        'kategori',
        'penerbit',
        'penulis',
        'ISBN',
        'isi',
        'cover',
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
}

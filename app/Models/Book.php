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
        if ($this->isi && (str_starts_with($this->isi, 'http://') || str_starts_with($this->isi, 'https://'))) {
            return $this->isi;
        }
        return $this->isi ? url(Storage::url($this->isi)) : null;
    }

    // Accessor untuk URL Cover
    public function getCoverUrlAttribute()
    {
        if ($this->cover && (str_starts_with($this->cover, 'http://') || str_starts_with($this->cover, 'https://'))) {
            return $this->cover;
        }
        return $this->cover ? url(Storage::url($this->cover)) : null;
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

    public function book1Class()
{
    return $this->hasMany(Book1Class::class, 'book_id');
}

    public function book2Class()
{
    return $this->hasMany(Book2Class::class, 'book_id');
}

    public function book3Class()
{
    return $this->hasMany(Book3Class::class, 'book_id');
}

    public function book4Class()
{
    return $this->hasMany(Book4Class::class, 'book_id');
}

    public function book5Class()
{
    return $this->hasMany(Book5Class::class, 'book_id');
}

    public function book6Class()
{
    return $this->hasMany(Book6Class::class, 'book_id');
}

    public function book7Class()
{
    return $this->hasMany(Book7Class::class, 'book_id');
}

    public function book8Class()
{
    return $this->hasMany(Book8Class::class, 'book_id');
}

    public function book9Class()
{
    return $this->hasMany(Book9Class::class, 'book_id');
}
    public function book10Class()
{
    return $this->hasMany(Book10Class::class, 'book_id');
}

    public function book11Class()
{
    return $this->hasMany(Book11Class::class, 'book_id');
}

    public function book12Class()
{
    return $this->hasMany(Book12Class::class, 'book_id');
}

public function kunjunganBooks()
{
    return $this->hasMany(KunjunganBook::class);
}

}
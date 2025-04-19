<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book3Class extends Model
{
    protected $table = 'book_3_classes';

    protected $fillable = [
        'book_id', 'judul', 'deskripsi', 'sekolah', 'kategori',
        'penerbit', 'penulis', 'tahun', 'ISBN', 'cover', 'isi'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class,'book_id');
    }
}

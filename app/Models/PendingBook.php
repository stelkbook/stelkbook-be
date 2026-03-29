<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingBook extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'judul',
        'kategori',
        'kelas',
        'penulis',
        'penerbit',
        'deskripsi',
        'isi',
    ];
}

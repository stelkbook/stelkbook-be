<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 
    'username',
        'email',
        'kode',
        'role',
        'gender',
        'sekolah',
        'kelas',
        'avatar',
    'tanggal_kunjungan',
    'tanggal_kunjungan_hari',
    'tanggal_kunjungan_bulan',
    'tanggal_kunjungan_tahun'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute()
{
    return $this->avatar ? Storage::url($this->avatar) : null;
}
}

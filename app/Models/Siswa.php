<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Siswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'password',
        'nis',
        'gender',
        'sekolah',
        'kelas'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($siswa) {
            // Ambil data user terkait
            $user = $siswa->user;

            if ($user) {
                if (in_array($siswa->kelas, ['I', 'II', 'III', 'IV', 'V', 'VI'])) {
                    $siswa->sekolah = 'SD';
                } elseif (in_array($siswa->kelas, ['VII', 'VIII', 'IX'])) {
                    $siswa->sekolah = 'SMP';
                } elseif (in_array($siswa->kelas, ['X', 'XI', 'XII'])) {
                    $siswa->sekolah = 'SMK';
                }
            }
        });

        static::updating(function ($siswa) {
            // Pastikan logika ini juga berjalan saat data diupdate
            $user = $siswa->user;

            if ($user) {
                if (in_array($siswa->kelas, ['I', 'II', 'III', 'IV', 'V', 'VI'])) {
                    $siswa->sekolah = 'SD';
                } elseif (in_array($siswa->kelas, ['VII', 'VIII', 'IX'])) {
                    $siswa->sekolah = 'SMP';
                } elseif (in_array($siswa->kelas, ['X', 'XI', 'XII'])) {
                    $siswa->sekolah = 'SMK';
                }
            }
        });
    }
    
    // Relasi dengan User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
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
        'kelas',
        'avatar'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($siswa) {
            // Set sekolah berdasarkan kelas
            if (in_array($siswa->kelas, ['I', 'II', 'III', 'IV', 'V', 'VI'])) {
                $siswa->sekolah = 'SD';
            } elseif (in_array($siswa->kelas, ['VII', 'VIII', 'IX'])) {
                $siswa->sekolah = 'SMP';
            } elseif (in_array($siswa->kelas, ['X', 'XI', 'XII'])) {
                $siswa->sekolah = 'SMK';
            }
        });

        static::updating(function ($siswa) {
            // Set sekolah berdasarkan kelas saat diupdate
            if (in_array($siswa->kelas, ['I', 'II', 'III', 'IV', 'V', 'VI'])) {
                $siswa->sekolah = 'SD';
            } elseif (in_array($siswa->kelas, ['VII', 'VIII', 'IX'])) {
                $siswa->sekolah = 'SMP';
            } elseif (in_array($siswa->kelas, ['X', 'XI', 'XII'])) {
                $siswa->sekolah = 'SMK';
            }
        });
    }

    // Relasi dengan User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $appends = ['avatar_url'];


    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    // Relasi ke SdSiswa
    public function sdSiswas(): HasOne
    {
        return $this->hasOne(SdSiswa::class, 'user_id', 'user_id');
    }

    // Relasi ke SmpSiswa
    public function smpSiswas(): HasOne
    {
        return $this->hasOne(SmpSiswa::class, 'user_id', 'user_id');
    }

    // Relasi ke SmkSiswa
    public function smkSiswas(): HasOne
    {
        return $this->hasOne(SmkSiswa::class, 'user_id', 'user_id');
    }

    // Method untuk mengambil relasi yang sesuai berdasarkan sekolah
    public function detailSiswa()
    {
        switch ($this->sekolah) {
            case 'SD':
                return $this->sdSiswa;
            case 'SMP':
                return $this->smpSiswa;
            case 'SMK':
                return $this->smkSiswa;
            default:
                return null;
        }
    }
}
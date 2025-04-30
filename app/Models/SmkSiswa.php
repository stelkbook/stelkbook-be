<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
class SmkSiswa extends Model
{
    use HasFactory;

    protected $table = 'smk_siswas';

    protected $fillable = [
        'user_id',
        'siswa_id',
        'username',
        'email',
        'password',
        'nis',
        'gender',
        'sekolah',
        'kelas',
        'avatar'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = ['avatar_url'];


    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    public function siswas() : BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'user_id', 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($siswa) {
            // Set sekolah berdasarkan kelas
            if (in_array($siswa->kelas, ['X', 'XI', 'XII'])) {
                $siswa->sekolah = 'SMK';
            }
        });

        static::updating(function ($siswa) {
            // Set sekolah berdasarkan kelas saat diupdate
            if (in_array($siswa->kelas, ['X', 'XI', 'XII'])) {
                $siswa->sekolah = 'SMK';
            }
        });
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
class SdSiswa extends Model
{
    use HasFactory;

    protected $table = 'sd_siswas';

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
    public function siswas(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'user_id', 'user_id');
    }

    protected $appends = ['avatar_url'];


    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($siswa) {
            // Set sekolah berdasarkan kelas
            if (in_array($siswa->kelas, ['I', 'II', 'III', 'IV', 'V', 'VI'])) {
                $siswa->sekolah = 'SD';
            }
        });

        static::updating(function ($siswa) {
            // Set sekolah berdasarkan kelas saat diupdate
            if (in_array($siswa->kelas, ['I', 'II', 'III', 'IV', 'V', 'VI'])) {
                $siswa->sekolah = 'SD';
            }
        });
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdGuru extends Model
{
    use HasFactory;

    protected $table = 'sd_gurus';

    protected $fillable = [
        'user_id',
        'guru_id',
        'username',
        'email',
        'password',
        'nip',
        'gender',
        'sekolah',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gurus(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($guru) {
            // Set sekolah to SD by default
            $guru->sekolah = 'SD';
        });

        static::updating(function ($guru) {
            // Ensure sekolah remains SD when updated
            $guru->sekolah = 'SD';
        });
    }
}
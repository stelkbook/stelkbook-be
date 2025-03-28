<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmpGuru extends Model
{
    use HasFactory;

    protected $table = 'smp_gurus';

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

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($guru) {
            $guru->sekolah = 'SMP';
        });

        static::updating(function ($guru) {
            $guru->sekolah = 'SMP';
        });
    }
}
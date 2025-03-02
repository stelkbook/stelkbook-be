<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'kode',
        'role',
        'gender',
        'sekolah'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'gender' => 'string',
        'kode' => 'integer'
    ];

    /**
     * Mutator to hash the password automatically.
     */
    public function setSekolahAttribute($value)
    {
        if (in_array($this->attributes['role'], ['Siswa', 'Guru'])) {
            $this->attributes['sekolah'] = $value;
        } else {
            $this->attributes['sekolah'] = null;
        }
    }

    public function siswas(): HasOne
    {
        return $this->hasOne(Siswa::class,'user_id');
    }

    /**
     * Relasi ke tabel Guru.
     */
    public function gurus(): HasOne
    {
        return $this->hasOne(Guru::class,'user_id');
    }

    /**
     * Relasi ke tabel Perpus.
     */
    public function perpuses(): HasOne
    {
        return $this->hasOne(Perpus::class,'user_id');
    }
}

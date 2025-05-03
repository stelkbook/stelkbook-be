<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approve extends Model
{
    use HasFactory;

    protected $table = 'tb_approve';

    protected $fillable = [
        'username',
        'email',
        'password',
        'kode',
        'role',
        'gender',
        'sekolah',
        'kelas',
        'avatar'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'gender' => 'string',
        'kode' => 'integer'
    ];
}

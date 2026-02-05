<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin_123'),
            'kode' => '412345',
            'role' => 'Admin',
            'gender' => 'Laki-Laki',
            'is_approved' => true,
        ]);

        // Siswa
        User::create([
            'username' => 'siswa',
            'email' => 'siswa@example.com',
            'password' => Hash::make('Siswa_123'),
            'kode' => '512345',
            'role' => 'Siswa',
            'gender' => 'Laki-Laki',
            'sekolah' => 'SMK',
            'kelas' => 'X',
            'is_approved' => true,
        ]);

        // Guru
        User::create([
            'username' => 'guru',
            'email' => 'guru@example.com',
            'password' => Hash::make('Guru_123'),
            'kode' => '212345',
            'role' => 'Guru',
            'gender' => 'Perempuan',
            'sekolah' => 'SMK',
            'is_approved' => true,
        ]);
    }
}
